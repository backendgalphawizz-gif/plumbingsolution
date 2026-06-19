@extends('admin.layouts.app')
@section('title', 'Bulk Orders')
@section('page-title', 'Bulk Order Management')
@section('page-subtitle', 'Review requirements and manage quotations')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Workflow Status</label>
        <select name="status" class="admin-input">
            <option value="">All stages</option>
            @foreach(['requirement_submitted','admin_review','quotation_generated','quotation_sent','customer_approved','customer_rejected','order_created'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ str_replace('_', ' ', ucfirst($s)) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Bulk Orders', 'meta' => number_format($bulkOrders->total()).' requests found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.bulk-orders.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Reference</th><th>Customer</th><th>Status</th><th>Files</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($bulkOrders as $bulkOrder)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $bulkOrder->reference_number }}</td>
                    <td>{{ $bulkOrder->user?->name }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $bulkOrder->status])</td>
                    <td>{{ $bulkOrder->files->count() }} file(s)</td>
                    <td class="text-sm text-slate-500">{{ $bulkOrder->created_at->format('M d, Y') }}</td>
                    <td><a href="{{ route('admin.bulk-orders.show', $bulkOrder) }}" class="action-btn">Review</a></td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state"><p>No bulk orders found.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $bulkOrders->links() }}@endslot
@endcomponent
@endsection
