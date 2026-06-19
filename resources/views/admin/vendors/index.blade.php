@extends('admin.layouts.app')
@section('title', 'Vendors')
@section('page-title', 'Vendor Management')
@section('page-subtitle', 'Approve shops, manage documents and vendor accounts')

@section('content')
<div class="page-toolbar">
    <div class="stat-tabs">@include('admin.partials._vendor-stats', compact('stats'))</div>
    @include('admin.partials.btn-create', ['href' => route('admin.vendors.create'), 'label' => 'Add Vendor'])
</div>

@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Shop, owner, mobile, GST..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','approved','rejected','suspended'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Vendor List', 'meta' => number_format($vendors->total()).' vendors found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.vendors.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Shop</th><th>Owner</th><th>Mobile</th><th>GST</th><th>Status</th><th>Products</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td><div class="user-name cell-truncate" title="{{ $vendor->shop_name }}">{{ $vendor->shop_name }}</div></td>
                    <td class="cell-truncate" title="{{ $vendor->owner_name }}">{{ $vendor->owner_name }}</td>
                    <td>{{ $vendor->mobile }}</td>
                    <td class="text-sm text-slate-500">{{ $vendor->gst_number ?? '—' }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $vendor->status])</td>
                    <td><span class="font-semibold">{{ $vendor->products_count }}</span></td>
                    <td class="text-sm text-slate-500">{{ $vendor->created_at->format('M d, Y') }}</td>
                    <td><div class="action-group"><a href="{{ route('admin.vendors.show', $vendor) }}" class="action-btn">View</a><a href="{{ route('admin.vendors.edit', $vendor) }}" class="action-btn">Edit</a></div></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><p>No vendors match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $vendors->links() }}@endslot
@endcomponent
@endsection
