<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search' => V::searchRules(),
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $products = Product::where('status', true)
            ->with(['vendor', 'images', 'category', 'subcategory'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->subcategory_id, fn ($q, $id) => $q->where('subcategory_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('product_name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($products->items())->map(fn ($p) => UserApiFormatter::product($p)),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        if (! $product->status) {
            return $this->error('Product not available.', 404);
        }

        $product->load(['vendor', 'images', 'category', 'subcategory', 'variants', 'reviews' => fn ($q) => $q->with('user')->latest()->limit(10)]);
        $product->loadAvg('reviews', 'rating');
        $product->loadCount('reviews');

        return $this->success(UserApiFormatter::product($product, detailed: true) + [
            'variants' => $product->variants->where('status', true)->values(),
        ]);
    }
}
