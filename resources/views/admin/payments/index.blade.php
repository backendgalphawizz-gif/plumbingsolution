@extends('admin.layouts.app')
@section('title', 'Payments')
@section('page-title', 'Payment Management')
@section('page-subtitle', 'View transactions and process refunds')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Payment Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','completed','failed','refunded'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Payment Method</label>
        <select name="method" class="admin-input">
            <option value="">All methods</option>
            @foreach(['razorpay','phonepe','cod'] as $m)
                <option value="{{ $m }}" @selected(request('method')===$m)>{{ ucfirst($m) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Transactions', 'meta' => number_format($payments->total()).' payments found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.payments.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Payment ID</th><th>Customer</th><th>Method</th><th>Amount</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($payments as $payment)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $payment->payment_id }}</td>
                    <td>{{ $payment->user?->name }}</td>
                    <td class="capitalize">{{ $payment->method->value ?? $payment->method }}</td>
                    <td class="font-semibold">₹{{ number_format($payment->amount, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $payment->status])</td>
                    <td class="text-sm text-slate-500">{{ $payment->created_at->format('M d, Y') }}</td>
                    <td><a href="{{ route('admin.payments.show', $payment) }}" class="action-btn">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><p>No payments match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $payments->links() }}@endslot
@endcomponent
@endsection
