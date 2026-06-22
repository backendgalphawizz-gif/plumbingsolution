<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $request->validate([
            'search' => V::searchRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $products = Product::query()
            ->where('vendor_id', $vendor->id)
            ->with([
                'category',
                'subcategory',
                'images',
                'vendor',
                'reviews' => fn ($q) => $q->with('user')->latest()->limit(20),
            ])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($products->items())->map(fn ($product) => VendorApiFormatter::product($product, detailed: true))->values(),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($response = $this->ensureApproved($vendor)) {
            return $response;
        }

        $data = $request->validate([
            'product_name' => ['required', 'string', V::maxRule('product_name')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'sku' => ['nullable', 'string', V::maxRule('sku'), 'unique:products,sku'],
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'product_name' => $data['product_name'],
            'slug' => $this->uniqueSlug($data['product_name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => $data['stock'],
            'sku' => $data['sku'] ?? $this->generateSku($vendor->id),
            'status' => true,
        ]);

        $this->syncImages($request, $product);

        $product->load(['category', 'subcategory', 'images', 'vendor']);
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');

        return $this->success(VendorApiFormatter::product($product, detailed: true), 'Product created.', 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($product->vendor_id !== $vendor->id) {
            return $this->error('Product not found.', 404);
        }

        $product->load(['category', 'subcategory', 'images', 'vendor', 'reviews' => fn ($q) => $q->with('user')->latest()->limit(20)]);
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');

        return $this->success(VendorApiFormatter::product($product, detailed: true));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($product->vendor_id !== $vendor->id) {
            return $this->error('Product not found.', 404);
        }

        if ($response = $this->ensureApproved($vendor)) {
            return $response;
        }

        $data = $request->validate([
            'product_name' => ['sometimes', 'string', V::maxRule('product_name')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'price' => ['sometimes', 'numeric', 'min:0', 'max:99999999.99'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'stock' => ['sometimes', 'integer', 'min:0', 'max:999999'],
            'sku' => ['sometimes', 'string', V::maxRule('sku'), 'unique:products,sku,'.$product->id],
            'status' => ['sometimes', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:5120'],
            'remove_image_ids' => ['nullable', 'array'],
            'remove_image_ids.*' => ['integer', 'exists:product_images,id'],
        ]);

        if (isset($data['product_name']) && ! isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['product_name'], $product->id);
        }

        $product->update(collect($data)->except(['images', 'remove_image_ids'])->toArray());

        if ($request->filled('remove_image_ids')) {
            $images = ProductImage::query()
                ->where('product_id', $product->id)
                ->whereIn('id', $request->input('remove_image_ids'))
                ->get();

            foreach ($images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }
        }

        $this->syncImages($request, $product);

        $product->load(['category', 'subcategory', 'images', 'vendor', 'reviews' => fn ($q) => $q->with('user')->latest()->limit(20)]);
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');

        return $this->success(VendorApiFormatter::product($product, detailed: true), 'Product updated.');
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($product->vendor_id !== $vendor->id) {
            return $this->error('Product not found.', 404);
        }

        if ($response = $this->ensureApproved($vendor)) {
            return $response;
        }

        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $product->delete();

        return $this->success(null, 'Product deleted.');
    }

    private function syncImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $existingCount = $product->images()->count();

        foreach ($request->file('images') as $i => $file) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $file->store('products', 'public'),
                'is_primary' => $existingCount === 0 && $i === 0,
                'sort_order' => $existingCount + $i,
            ]);
        }
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Product::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function generateSku(int $vendorId): string
    {
        do {
            $sku = 'VND-'.$vendorId.'-'.strtoupper(Str::random(6));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }
}
