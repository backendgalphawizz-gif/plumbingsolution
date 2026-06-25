@extends('admin.layouts.app')
@section('title', $product->product_name)
@section('page-title', 'Product Details')
@section('page-subtitle', 'Catalog information, pricing and inventory')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="form-card lg:col-span-2">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $product->product_name }}</h2>
                <p class="text-sm text-slate-500">SKU: {{ $product->sku }}</p>
            </div>
            @include('admin.partials.status-badge', ['status' => $product->status ? 'active' : 'inactive'])
        </div>

        @php
            $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        @endphp
        @if($primaryImage)
            <div class="mb-6">
                <img src="{{ asset('storage/'.$primaryImage->image_path) }}" alt="{{ $product->product_name }}" class="h-48 w-full max-w-md rounded-lg object-cover">
            </div>
        @endif

        @if($product->images->count() > 1)
            <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach($product->images as $image)
                    <a href="{{ asset('storage/'.$image->image_path) }}" target="_blank">
                        <img src="{{ asset('storage/'.$image->image_path) }}" alt="" class="h-24 w-full rounded-lg object-cover">
                    </a>
                @endforeach
            </div>
        @endif

        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="admin-label">Category</dt><dd class="mt-1">{{ $product->category?->name ?? '—' }}@if($product->subcategory)<span class="text-slate-400"> / {{ $product->subcategory->name }}</span>@endif</dd></div>
            <div><dt class="admin-label">Vendor</dt><dd class="mt-1">{{ $product->vendor?->shop_name ?? '—' }}</dd></div>
            <div><dt class="admin-label">Price</dt><dd class="mt-1 font-semibold">₹{{ number_format($product->price, 2) }}</dd></div>
            <div><dt class="admin-label">Sale Price</dt><dd class="mt-1">{{ $product->sale_price ? '₹'.number_format($product->sale_price, 2) : '—' }}</dd></div>
            <div><dt class="admin-label">Stock</dt><dd class="mt-1">{{ $product->stock }}</dd></div>
            <div><dt class="admin-label">Created</dt><dd class="mt-1">{{ $product->created_at->format('M d, Y') }}</dd></div>
            <div class="sm:col-span-2"><dt class="admin-label">Description</dt><dd class="mt-1">{{ $product->description ?? '—' }}</dd></div>
        </dl>

        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.products.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-primary btn-sm">Edit Product</a>
        </div>
    </div>

    <div class="detail-panel">
        <h3 class="detail-panel-title">Quick Info</h3>
        <div class="detail-row">
            <span>Status</span>
            @include('admin.partials.status-badge', ['status' => $product->status ? 'active' : 'inactive'])
        </div>
        <div class="detail-row">
            <span>Images</span>
            <span class="font-semibold">{{ $product->images->count() }}</span>
        </div>
    </div>
</div>
@endsection
