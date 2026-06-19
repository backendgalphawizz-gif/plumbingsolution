@extends('admin.layouts.app')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')
@section('page-title', $product->exists ? 'Edit Product' : 'Add Product')
@section('page-subtitle', 'Catalog details, pricing and inventory')

@section('content')
<div class="form-card mx-auto max-w-3xl">
    <div class="form-section-title">{{ $product->exists ? 'Product Details' : 'New Product' }}</div>
    <div class="form-section-desc">Link to category, vendor and set pricing/stock.</div>

    <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($product->exists) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">@include('admin.partials.form-field', ['label' => 'Product Name', 'name' => 'product_name', 'limit' => 'product_name', 'value' => old('product_name', $product->product_name), 'required' => true])</div>
            <div>
                <label class="admin-label">Category *</label>
                <select name="category_id" required class="admin-input">
                    <option value="">Select category</option>
                    @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(old('category_id', $product->category_id) == $cat->id)>{{ $cat->name }}</option>@endforeach
                </select>
                @error('category_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Subcategory</label>
                <select name="subcategory_id" class="admin-input">
                    <option value="">None</option>
                    @foreach($categories as $cat)
                        @if($cat->subcategories->isNotEmpty())
                            <optgroup label="{{ $cat->name }}">
                                @foreach($cat->subcategories as $sub)<option value="{{ $sub->id }}" @selected(old('subcategory_id', $product->subcategory_id) == $sub->id)>{{ $sub->name }}</option>@endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            </div>
            <div>
                <label class="admin-label">Vendor *</label>
                <select name="vendor_id" required class="admin-input">
                    <option value="">Select vendor</option>
                    @foreach($vendors as $vendor)<option value="{{ $vendor->id }}" @selected(old('vendor_id', $product->vendor_id) == $vendor->id)>{{ $vendor->shop_name }}</option>@endforeach
                </select>
                @if($vendors->isEmpty())<p class="field-hint text-amber-600">No approved vendors yet.</p>@endif
                @error('vendor_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>@include('admin.partials.form-field', ['label' => 'SKU', 'name' => 'sku', 'limit' => 'sku', 'value' => old('sku', $product->sku), 'required' => true])</div>
            <div>
                <label class="admin-label">Price *</label>
                <input type="number" step="0.01" min="0" max="99999999.99" name="price" value="{{ old('price', $product->price) }}" required class="admin-input">
                @error('price')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Sale Price</label>
                <input type="number" step="0.01" min="0" max="99999999.99" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="admin-input">
            </div>
            <div>
                <label class="admin-label">Stock *</label>
                <input type="number" name="stock" min="0" max="999999" value="{{ old('stock', $product->stock ?? 0) }}" required class="admin-input">
                @error('stock')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2">@include('admin.partials.form-field', ['label' => 'Description', 'name' => 'description', 'type' => 'textarea', 'limit' => 'description', 'value' => old('description', $product->description), 'rows' => 4])</div>
            <div><label class="admin-label">Images</label><input type="file" name="images[]" multiple accept="image/*" class="admin-input !h-auto !py-2.5"></div>
            <div class="flex items-end"><label class="flex items-center gap-2 text-sm font-medium text-slate-700"><input type="checkbox" name="status" value="1" @checked(old('status', $product->status ?? true)) class="rounded text-emerald-600"> Active product</label></div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary">Cancel</a>
            <button class="btn btn-primary">Save Product</button>
        </div>
    </form>
</div>
@endsection
