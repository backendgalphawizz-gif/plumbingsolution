@extends('admin.layouts.app')
@section('title', 'Order Returns')
@section('page-title', 'Order Returns')
@section('page-subtitle', 'Review and approve customer return requests')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Return #, order #, product, customer..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','approved','rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
@endcomponent

@component('admin.partials.data-card', ['title' => 'Return Requests', 'meta' => number_format($returns->total()).' requests found'.($pendingCount ? ' · '.$pendingCount.' pending' : '')])
    <table class="admin-table">
        <thead>
            <tr>
                <th>Return #</th>
                <th>Order</th>
                <th>Product</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Refund</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($returns as $return)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $return->return_number }}</td>
                    <td>{{ $return->order?->order_number ?? '—' }}</td>
                    <td>{{ $return->orderItem?->product_name ?? '—' }}</td>
                    <td>
                        <div>{{ $return->user?->name ?? '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $return->user?->mobile }}</div>
                    </td>
                    <td>{{ $return->quantity }}</td>
                    <td class="font-semibold">₹{{ number_format($return->refund_amount, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $return->status])</td>
                    <td class="text-sm text-slate-500">{{ $return->created_at->format('M d, Y • g:i A') }}</td>
                    <td>
                        <a href="{{ route('admin.order-returns.show', $return) }}" class="action-btn">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9"><div class="empty-state"><p>No return requests match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $returns->links() }}@endslot
@endcomponent
@endsection
