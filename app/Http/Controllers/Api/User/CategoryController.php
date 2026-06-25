<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $categories = Category::where('status', true)
            ->with(['subcategories' => fn ($q) => $q->where('status', true)->orderBy('sort_order')])
            ->withCount(['products' => fn ($q) => $q->where('status', true)])
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'image' => $c->image ? asset('storage/'.$c->image) : null,
                'products_count' => $c->products_count,
                'subcategories' => $c->subcategories->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'slug' => $s->slug,
                    'image' => $s->image ? asset('storage/'.$s->image) : null,
                ]),
            ]);

        return $this->success($categories);
    }
}
