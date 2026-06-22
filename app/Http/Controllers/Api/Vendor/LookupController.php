<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class LookupController extends Controller
{
    use ApiResponse;

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('status', true)
            ->with(['subcategories' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug']);

        return $this->success([
            'categories' => $categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'subcategories' => $category->subcategories->map(fn ($sub) => [
                    'id' => $sub->id,
                    'name' => $sub->name,
                    'slug' => $sub->slug,
                ])->values(),
            ])->values(),
        ]);
    }
}
