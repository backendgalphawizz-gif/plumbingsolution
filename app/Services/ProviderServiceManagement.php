<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceImage;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProviderServiceManagement
{
    public function prepareRequest(Request $request): void
    {
        if ($request->has('is_available')) {
            $request->merge(['is_available' => $request->boolean('is_available')]);
        }
    }

    public function storeRules(): array
    {
        return [
            'name' => ['required_without:service_name', 'string', V::maxRule('product_name')],
            'service_name' => ['required_without:name', 'string', V::maxRule('product_name')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['required_without:service_category_id', 'exists:service_categories,id'],
            'service_category_id' => ['required_without:category_id', 'exists:service_categories,id'],
            'is_available' => ['sometimes', 'boolean'],
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'name' => ['sometimes', 'string', V::maxRule('product_name')],
            'service_name' => ['sometimes', 'string', V::maxRule('product_name')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['sometimes', 'exists:service_categories,id'],
            'service_category_id' => ['sometimes', 'exists:service_categories,id'],
            'is_available' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'exists:service_images,id'],
        ];
    }

    public function create(ServiceProvider $provider, array $data, Request $request): Service
    {
        return DB::transaction(function () use ($provider, $data, $request) {
            $name = $data['name'] ?? $data['service_name'];
            $categoryId = $data['category_id'] ?? $data['service_category_id'];
            $price = $data['price'];
            $primaryPath = $request->file('images')[0]->store('services', 'public');

            $service = Service::create([
                'service_category_id' => $categoryId,
                'service_provider_id' => $provider->id,
                'name' => $name,
                'slug' => $this->uniqueSlug($name, $provider->id),
                'description' => $data['description'] ?? null,
                'image' => $primaryPath,
                'starting_price' => $price,
                'status' => true,
            ]);

            $this->syncImages($request, $service);
            $provider->services()->attach($service->id, [
                'price' => $price,
                'is_available' => $data['is_available'] ?? true,
            ]);

            return $service->load(['category', 'images']);
        });
    }

    public function update(ServiceProvider $provider, Service $service, array $data, Request $request): Service
    {
        return DB::transaction(function () use ($provider, $service, $data, $request) {
            $owned = $service->service_provider_id === $provider->id;

            if ($owned) {
                $updates = [];

                if (isset($data['name']) || isset($data['service_name'])) {
                    $name = $data['name'] ?? $data['service_name'];
                    $updates['name'] = $name;
                    $updates['slug'] = $this->uniqueSlug($name, $provider->id, $service->id);
                }

                if (array_key_exists('description', $data)) {
                    $updates['description'] = $data['description'];
                }

                if (isset($data['category_id']) || isset($data['service_category_id'])) {
                    $updates['service_category_id'] = $data['category_id'] ?? $data['service_category_id'];
                }

                if (isset($data['price'])) {
                    $updates['starting_price'] = $data['price'];
                }

                if ($updates !== []) {
                    $service->update($updates);
                }

                if ($request->filled('remove_image_ids')) {
                    $this->removeImages($service, $request->input('remove_image_ids'));
                }

                $this->syncImages($request, $service);
                $this->refreshPrimaryImage($service);
            }

            $pivotUpdates = array_filter([
                'price' => $data['price'] ?? null,
                'is_available' => array_key_exists('is_available', $data) ? $data['is_available'] : null,
            ], fn ($value) => $value !== null);

            if ($pivotUpdates !== []) {
                $provider->services()->updateExistingPivot($service->id, $pivotUpdates);
            }

            return $service->fresh()->load(['category', 'images']);
        });
    }

    public function delete(ServiceProvider $provider, Service $service): void
    {
        if ($service->service_provider_id === $provider->id) {
            foreach ($service->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            if ($service->image) {
                Storage::disk('public')->delete($service->image);
            }

            $provider->services()->detach($service->id);
            $service->delete();

            return;
        }

        $provider->services()->detach($service->id);
    }

    public function ownedBy(ServiceProvider $provider, int $serviceId): ?Service
    {
        return $provider->services()
            ->with(['category', 'images'])
            ->where('services.id', $serviceId)
            ->first();
    }

    private function syncImages(Request $request, Service $service): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $existingCount = $service->images()->count();

        foreach ($request->file('images') as $i => $file) {
            ServiceImage::create([
                'service_id' => $service->id,
                'image_path' => $file->store('services', 'public'),
                'is_primary' => $existingCount === 0 && $i === 0,
                'sort_order' => $existingCount + $i,
            ]);
        }
    }

    private function removeImages(Service $service, array $imageIds): void
    {
        $images = ServiceImage::query()
            ->where('service_id', $service->id)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }
    }

    private function refreshPrimaryImage(Service $service): void
    {
        $service->load('images');
        $primary = $service->images->firstWhere('is_primary', true) ?? $service->images->first();

        if ($primary) {
            $service->update(['image' => $primary->image_path]);
            $service->images()->whereKeyNot($primary->id)->update(['is_primary' => false]);
            $primary->update(['is_primary' => true]);
        }
    }

    private function uniqueSlug(string $name, int $providerId, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Service::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$providerId.'-'.($counter++);
        }

        return $slug;
    }
}
