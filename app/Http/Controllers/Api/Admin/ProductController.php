<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $products = Product::with(['category:id,name', 'subcategory:id,name', 'vendor:id,shop_name'])
            ->when($request->search, fn ($q, $s) => $q->where('product_name', 'like', "%{$s}%"))
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->vendor_id, fn ($q, $id) => $q->where('vendor_id', $id))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($products);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'product_name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:products,slug'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'sku' => ['required', 'string', 'unique:products,sku'],
            'status' => ['boolean'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'],
            'variants' => ['nullable', 'array'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['product_name']);

        $product = Product::create(collect($data)->except(['images', 'variants'])->toArray());

        if ($request->has('images')) {
            foreach ($request->images as $i => $path) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'is_primary' => $i === 0,
                    'sort_order' => $i,
                ]);
            }
        }

        if ($request->has('variants')) {
            foreach ($request->variants as $variant) {
                ProductVariant::create(array_merge($variant, ['product_id' => $product->id]));
            }
        }

        return $this->success($product->load(['images', 'variants']), 'Product created.', 201);
    }

    public function show(Product $product): JsonResponse
    {
        return $this->success($product->load(['category', 'subcategory', 'vendor', 'images', 'variants']));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'vendor_id' => ['sometimes', 'exists:vendors,id'],
            'product_name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'unique:products,slug,'.$product->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['sometimes', 'integer', 'min:0'],
            'sku' => ['sometimes', 'string', 'unique:products,sku,'.$product->id],
            'status' => ['boolean'],
        ]);

        $product->update($data);

        return $this->success($product->fresh()->load(['images', 'variants']), 'Product updated.');
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return $this->success(null, 'Product deleted.');
    }
}
