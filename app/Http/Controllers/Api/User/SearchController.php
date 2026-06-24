<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Product;
use App\Models\Service;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SearchController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q' => V::searchRules(),
            'search' => V::searchRules(),
            'type' => ['nullable', Rule::in(['all', 'product', 'service'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'service_category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
        ]);

        $query = trim((string) ($request->q ?? $request->search ?? ''));

        if ($query === '') {
            return $this->error('Search query is required.', 422);
        }

        $type = $request->get('type', 'all');
        $perPage = $request->integer('per_page', 15);

        $product = ['items' => [], 'total' => 0];
        $service = ['items' => [], 'total' => 0];

        if (in_array($type, ['all', 'product'], true)) {
            $products = Product::where('status', true)
                ->with(['vendor', 'images', 'category', 'subcategory'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
                ->when($request->subcategory_id, fn ($q, $id) => $q->where('subcategory_id', $id))
                ->where(function ($q) use ($query) {
                    $q->where('product_name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->latest()
                ->paginate($perPage);

            $product = [
                'items' => collect($products->items())->map(fn ($p) => UserApiFormatter::product($p))->values(),
                'total' => $products->total(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                ],
            ];
        }

        if (in_array($type, ['all', 'service'], true)) {
            $services = Service::where('status', true)
                ->with('category')
                ->when($request->service_category_id, fn ($q, $id) => $q->where('service_category_id', $id))
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                })
                ->orderBy('sort_order')
                ->paginate($perPage);

            $service = [
                'items' => collect($services->items())->map(fn ($s) => UserApiFormatter::service($s))->values(),
                'total' => $services->total(),
                'pagination' => [
                    'current_page' => $services->currentPage(),
                    'last_page' => $services->lastPage(),
                    'per_page' => $services->perPage(),
                ],
            ];
        }

        return $this->success([
            'query' => $query,
            'type' => $type,
            'product' => $product,
            'service' => $service,
        ]);
    }
}
