<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesUploads;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceImage;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ServiceController extends Controller
{
    use ExportsAdminTable, HandlesUploads;

    public function index(Request $request): View
    {
        $services = $this->filteredServices($request)->paginate(15)->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'providers' => ServiceProvider::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.services.form', [
            'service' => new Service,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'providers' => ServiceProvider::where('status', 'approved')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedService($request);
        $primaryPath = null;

        if ($request->hasFile('images')) {
            $primaryPath = $request->file('images')[0]->store('services', 'public');
        }

        $service = Service::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'image' => $primaryPath,
            'status' => $request->boolean('status', true),
        ]);

        $this->syncImages($request, $service);

        if ($service->service_provider_id) {
            $service->serviceProvider?->services()->syncWithoutDetaching([
                $service->id => [
                    'price' => $data['starting_price'],
                    'is_available' => true,
                ],
            ]);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service created.');
    }

    public function edit(Service $service): View
    {
        $service->load(['images', 'category', 'serviceProvider']);

        return view('admin.services.form', [
            'service' => $service,
            'categories' => ServiceCategory::orderBy('name')->get(),
            'providers' => ServiceProvider::where('status', 'approved')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $this->validatedService($request, $service);

        $service->update([
            ...$data,
            'slug' => $this->uniqueSlug($data['name'], $service->id),
            'status' => $request->boolean('status', true),
        ]);

        if ($request->filled('remove_image_ids')) {
            $this->removeImages($service, $request->input('remove_image_ids'));
        }

        $this->syncImages($request, $service);
        $this->refreshPrimaryImage($service);

        if ($service->service_provider_id) {
            $service->serviceProvider?->services()->updateExistingPivot($service->id, [
                'price' => $data['starting_price'],
            ]);
        }

        return redirect()->route('admin.services.index')->with('success', 'Service updated.');
    }

    public function show(Service $service): View
    {
        $service->load(['category', 'serviceProvider', 'images', 'providers']);

        return view('admin.services.show', compact('service'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service deleted.');
    }

    public function export(Request $request)
    {
        $services = $this->filteredServices($request)->get();

        return $this->exportResponse(
            $request,
            'services',
            'Service Catalog',
            ['Service', 'Category', 'Provider', 'Price', 'Status', 'Created Date'],
            $services->map(fn (Service $service) => [
                $service->name,
                $service->category?->name ?? '',
                $service->serviceProvider?->name ?? 'Platform',
                number_format((float) $service->starting_price, 2),
                $service->status ? 'Active' : 'Inactive',
                $service->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredServices(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return Service::query()
            ->with(['category', 'serviceProvider'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            }))
            ->when($request->category_id, fn ($q, $id) => $q->where('service_category_id', $id))
            ->when($request->provider_id, fn ($q, $id) => $q->where('service_provider_id', $id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
            ->when($request->source === 'provider', fn ($q) => $q->whereNotNull('service_provider_id'))
            ->when($request->source === 'platform', fn ($q) => $q->whereNull('service_provider_id'))
            ->latest();
    }

    private function validatedService(Request $request, ?Service $service = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', V::maxRule('product_name')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'starting_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'images' => [$service ? 'nullable' : 'nullable', 'array', 'max:5'],
            'images.*' => V::imageRules(required: false),
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'exists:service_images,id'],
        ]);

        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'service_category_id' => $data['service_category_id'],
            'service_provider_id' => $data['service_provider_id'] ?? null,
            'starting_price' => $data['starting_price'],
        ];
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
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Service::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.($counter++);
        }

        return $slug;
    }
}
