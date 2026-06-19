<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('subcategories', 'products')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->paginate($request->get('per_page', 15));

        return $this->success($categories);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'unique:categories,slug'],
            'image' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);

        return $this->success($category, 'Category created.', 201);
    }

    public function show(Category $category): JsonResponse
    {
        return $this->success($category->load('subcategories'));
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'unique:categories,slug,'.$category->id],
            'image' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $category->update($data);

        return $this->success($category->fresh(), 'Category updated.');
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return $this->success(null, 'Category deleted.');
    }

    public function subcategories(Request $request): JsonResponse
    {
        $subcategories = Subcategory::with('category:id,name')
            ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->orderBy('sort_order')
            ->paginate($request->get('per_page', 15));

        return $this->success($subcategories);
    }

    public function storeSubcategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string'],
            'image' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $subcategory = Subcategory::create($data);

        return $this->success($subcategory->load('category'), 'Subcategory created.', 201);
    }

    public function updateSubcategory(Request $request, Subcategory $subcategory): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['sometimes', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string'],
            'image' => ['nullable', 'string'],
            'status' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $subcategory->update($data);

        return $this->success($subcategory->fresh()->load('category'), 'Subcategory updated.');
    }

    public function destroySubcategory(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();

        return $this->success(null, 'Subcategory deleted.');
    }
}
