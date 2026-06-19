<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesUploads;
use App\Models\Category;
use App\Models\Subcategory;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use ExportsAdminTable, HandlesUploads;

    public function index(Request $request): View
    {
        $categories = $this->filteredCategories($request)->paginate(15)->withQueryString();

        $counts = [
            'categories' => Category::count(),
            'subcategories' => Subcategory::count(),
        ];

        return view('admin.categories.index', compact('categories', 'counts'));
    }

    public function exportCategories(Request $request)
    {
        $categories = $this->filteredCategories($request)->get();

        return $this->exportResponse(
            $request,
            'categories',
            'Category List',
            ['Name', 'Slug', 'Subcategories', 'Products', 'Sort', 'Status', 'Created Date'],
            $categories->map(fn (Category $c) => [
                $c->name,
                $c->slug,
                $c->subcategories_count,
                $c->products_count,
                $c->sort_order,
                $c->status ? 'Active' : 'Inactive',
                $c->created_at->format('M d, Y'),
            ])
        );
    }

    public function subcategoriesIndex(Request $request): View
    {
        $subcategories = $this->filteredSubcategories($request)->paginate(15)->withQueryString();
        $categories = Category::orderBy('name')->get();

        $counts = [
            'categories' => Category::count(),
            'subcategories' => Subcategory::count(),
        ];

        return view('admin.subcategories.index', compact('subcategories', 'categories', 'counts'));
    }

    public function exportSubcategories(Request $request)
    {
        $subcategories = $this->filteredSubcategories($request)->get();

        return $this->exportResponse(
            $request,
            'subcategories',
            'Subcategory List',
            ['Name', 'Parent Category', 'Slug', 'Products', 'Sort', 'Status', 'Created Date'],
            $subcategories->map(fn (Subcategory $s) => [
                $s->name,
                $s->category?->name ?? '',
                $s->slug,
                $s->products_count,
                $s->sort_order,
                $s->status ? 'Active' : 'Inactive',
                $s->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredCategories(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            Category::withCount('subcategories', 'products')
                ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->boolean('status')))
                ->orderBy('sort_order')
                ->orderBy('name'),
            $request
        );
    }

    private function filteredSubcategories(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            Subcategory::with('category')
                ->withCount('products')
                ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
                ->when($request->status !== null && $request->status !== '', fn ($q) => $q->where('status', $request->boolean('status')))
                ->orderBy('sort_order')
                ->orderBy('name'),
            $request
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->categoryRules());

        Category::create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'image' => $this->storeUploadedImage($request, 'image', 'categories'),
            'status' => $request->boolean('status', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate($this->categoryRules($category->id));

        $category->update([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'image' => $this->storeUploadedImage($request, 'image', 'categories', $category->image),
            'status' => $request->boolean('status'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()->route('admin.categories.index')->with('error', 'Cannot delete category with linked products.');
        }

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted successfully.');
    }

    public function storeSubcategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate($this->subcategoryRules());

        $slug = $data['slug'] ?? Str::slug($data['name']);

        if ($category->subcategories()->where('slug', $slug)->exists()) {
            return redirect()->route('admin.subcategories.index')->with('error', 'Subcategory slug already exists in this category.');
        }

        $category->subcategories()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'image' => $this->storeUploadedImage($request, 'image', 'subcategories'),
            'status' => $request->boolean('status', true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory created successfully.');
    }

    public function updateSubcategory(Request $request, Subcategory $subcategory): RedirectResponse
    {
        $data = $request->validate($this->subcategoryRules());

        $slug = $data['slug'] ?? Str::slug($data['name']);

        if ($subcategory->category->subcategories()->where('slug', $slug)->where('id', '!=', $subcategory->id)->exists()) {
            return redirect()->route('admin.subcategories.index')->with('error', 'Subcategory slug already exists in this category.');
        }

        $subcategory->update([
            'name' => $data['name'],
            'slug' => $slug,
            'image' => $this->storeUploadedImage($request, 'image', 'subcategories', $subcategory->image),
            'status' => $request->boolean('status'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory updated successfully.');
    }

    public function destroySubcategory(Subcategory $subcategory): RedirectResponse
    {
        if ($subcategory->products()->exists()) {
            return redirect()->route('admin.subcategories.index')->with('error', 'Cannot delete subcategory with linked products.');
        }

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        $subcategory->delete();

        return redirect()->route('admin.subcategories.index')->with('success', 'Subcategory deleted successfully.');
    }

    private function categoryRules(?int $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', V::maxRule('category_name'), 'regex:/^[\pL\s\-\'.&0-9]+$/u'],
            'slug' => ['nullable', 'string', V::maxRule('slug'), 'unique:categories,slug,'.($ignoreId ?? 'NULL')],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    private function subcategoryRules(): array
    {
        return [
            'name' => ['required', 'string', V::maxRule('category_name'), 'regex:/^[\pL\s\-\'.&0-9]+$/u'],
            'slug' => ['nullable', 'string', V::maxRule('slug')],
            'image' => ['nullable', 'image', 'max:2048'],
            'status' => ['nullable'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
