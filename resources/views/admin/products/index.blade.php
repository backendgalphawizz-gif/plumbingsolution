@extends('admin.layouts.app')
@section('title', 'Products')
@section('page-title', 'Product Management')
@section('page-subtitle', 'Manage catalog products linked to categories and vendors')

@section('content')
<div class="page-toolbar">
    <div></div>
    @include('admin.partials.btn-create', ['href' => route('admin.products.create'), 'label' => 'Add Product'])
</div>

@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Product name or SKU..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Category</label>
        <select name="category_id" class="admin-input">
            <option value="">All categories</option>
            @foreach($categories as $cat)<option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Vendor</label>
        <select name="vendor_id" class="admin-input">
            <option value="">All vendors</option>
            @foreach($vendors as $v)<option value="{{ $v->id }}" @selected(request('vendor_id') == $v->id)>{{ $v->shop_name }}</option>@endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Availability</label>
        <select name="status" class="admin-input">
            <option value="">All</option>
            <option value="1" @selected(request('status')==='1')>Active</option>
            <option value="0" @selected(request('status')==='0')>Inactive</option>
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Product Catalog', 'meta' => number_format($products->total()).' products found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.products.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Product</th><th>Category</th><th>Vendor</th><th>Price</th><th>Stock</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($products as $product)
                <tr>
                    <td><div class="user-name cell-truncate" title="{{ $product->product_name }}">{{ $product->product_name }}</div><div class="user-sub">{{ $product->sku }}</div></td>
                    <td class="text-sm">{{ $product->category?->name }}@if($product->subcategory)<span class="text-slate-400"> / {{ $product->subcategory->name }}</span>@endif</td>
                    <td class="text-sm">{{ $product->vendor?->shop_name ?? '—' }}</td>
                    <td class="font-semibold">₹{{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->stock }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $product->status ? 'active' : 'inactive'])</td>
                    <td class="text-sm text-slate-500">{{ $product->created_at->format('M d, Y') }}</td>
                    <td><div class="action-group"><a href="{{ route('admin.products.edit', $product) }}" class="action-btn">Edit</a>
                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button type="submit" class="action-btn danger">Delete</button></form></div></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><p>No products match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $products->links() }}@endslot
@endcomponent
@endsection
