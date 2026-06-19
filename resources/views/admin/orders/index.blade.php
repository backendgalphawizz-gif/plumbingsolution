@extends('admin.layouts.app')
@section('title', 'Orders')
@section('page-title', 'Order Management')
@section('page-subtitle', 'Track orders, update status and process cancellations')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Order Number</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search order #..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','accepted','packed','shipped','delivered','cancelled','returned','refunded'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Orders', 'meta' => number_format($orders->total()).' orders found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.orders.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($orders as $order)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $order->order_number }}</td>
                    <td>{{ $order->user?->name }}</td>
                    <td class="font-semibold">₹{{ number_format($order->total_amount, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $order->status])</td>
                    <td class="text-sm text-slate-500">{{ $order->created_at->format('M d, Y') }}</td>
                    <td><a href="{{ route('admin.orders.show', $order) }}" class="action-btn">Details</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><p>No orders match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $orders->links() }}@endslot
@endcomponent
@endsection
