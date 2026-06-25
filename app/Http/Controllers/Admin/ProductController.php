<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Http\Traits\HandlesUploads;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Vendor;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ExportsAdminTable, HandlesUploads;

    public function index(Request $request): View
    {
        $products = $this->filteredProducts($request)->paginate(15)->withQueryString();

        $categories = Category::orderBy('name')->get();
        $vendors = Vendor::where('status', 'approved')->orderBy('shop_name')->get();

        return view('admin.products.index', compact('products', 'categories', 'vendors'));
    }

    public function export(Request $request)
    {
        $products = $this->filteredProducts($request)->get();

        return $this->exportResponse(
            $request,
            'products',
            'Product Catalog',
            ['Product', 'SKU', 'Category', 'Vendor', 'Price', 'Stock', 'Status', 'Created Date'],
            $products->map(fn (Product $p) => [
                $p->product_name,
                $p->sku,
                $p->category?->name ?? '',
                $p->vendor?->shop_name ?? '',
                number_format((float) $p->price, 2),
                $p->stock,
                $p->status ? 'Active' : 'Inactive',
                $p->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredProducts(Request $request): Builder
    {
        $request->validate(['search' => V::searchRules()]);

        return $this->applyDateRange(
            Product::with(['category', 'subcategory', 'vendor', 'images'])
                ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                    $q->where('product_name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%");
                }))
                ->when($request->category_id, fn ($q, $id) => $q->where('category_id', $id))
                ->when($request->vendor_id, fn ($q, $id) => $q->where('vendor_id', $id))
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->boolean('status')))
                ->latest(),
            $request
        );
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product,
            'categories' => Category::with('subcategories')->orderBy('sort_order')->get(),
            'vendors' => Vendor::where('status', 'approved')->orderBy('shop_name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedProduct($request);

        $product = Product::create($data);

        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'subcategory', 'vendor', 'images']);

        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $product->load('images');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::with('subcategories')->orderBy('sort_order')->get(),
            'vendors' => Vendor::where('status', 'approved')->orderBy('shop_name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validatedProduct($request, $product));

        $this->syncImages($request, $product);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            return back()->with('error', 'Cannot delete product linked to existing orders. Deactivate it instead.');
        }

        foreach ($product->images as $image) {
            \Storage::disk('public')->delete($image->image_path);
        }
        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    private function validatedProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'product_name' => ['required', 'string', V::maxRule('product_name')],
            'slug' => ['nullable', 'string', V::maxRule('slug'), 'unique:products,slug,'.($product?->id ?? 'NULL')],
            'description' => ['nullable', 'string', V::maxRule('description')],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'sku' => ['required', 'string', V::maxRule('sku'), 'unique:products,sku,'.($product?->id ?? 'NULL')],
            'status' => ['nullable'],
            'images.*' => V::imageRules(required: false),
        ]);

        return [
            'category_id' => $data['category_id'],
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'vendor_id' => $data['vendor_id'],
            'product_name' => $data['product_name'],
            'slug' => $data['slug'] ?? Str::slug($data['product_name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'stock' => $data['stock'],
            'sku' => $data['sku'],
            'status' => $request->boolean('status', true),
        ];
    }

    private function syncImages(Request $request, Product $product): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        foreach ($request->file('images') as $i => $file) {
            $path = $file->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => $product->images()->count() === 0 && $i === 0,
                'sort_order' => $i,
            ]);
        }
    }
}
