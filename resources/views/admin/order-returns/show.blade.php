@extends('admin.layouts.app')
@section('title', $orderReturn->return_number)
@section('page-title', 'Return Request')
@section('page-subtitle', $orderReturn->order?->order_number)

@section('content')
<div class="form-card mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">{{ $orderReturn->return_number }}</h2>
            <p class="text-sm text-slate-500">Order {{ $orderReturn->order?->order_number }}</p>
        </div>
        @include('admin.partials.status-badge', ['status' => $orderReturn->status])
    </div>

    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div><dt class="admin-label">Product</dt><dd class="mt-1 font-medium">{{ $orderReturn->orderItem?->product_name ?? '—' }}</dd></div>
        <div><dt class="admin-label">SKU</dt><dd class="mt-1">{{ $orderReturn->orderItem?->sku ?? '—' }}</dd></div>
        <div><dt class="admin-label">Return Quantity</dt><dd class="mt-1">{{ $orderReturn->quantity }} / {{ $orderReturn->orderItem?->quantity ?? '—' }}</dd></div>
        <div><dt class="admin-label">Refund Amount</dt><dd class="mt-1 text-lg font-bold text-emerald-700">₹{{ number_format($orderReturn->refund_amount, 2) }}</dd></div>
        <div><dt class="admin-label">Customer</dt><dd class="mt-1">{{ $orderReturn->user?->name ?? '—' }}</dd></div>
        <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $orderReturn->user?->mobile ?? '—' }}</dd></div>
        <div><dt class="admin-label">Vendor</dt><dd class="mt-1">{{ $orderReturn->order?->vendor?->shop_name ?? '—' }}</dd></div>
        <div><dt class="admin-label">Requested On</dt><dd class="mt-1">{{ $orderReturn->created_at->format('M d, Y • g:i A') }}</dd></div>
        <div class="sm:col-span-2"><dt class="admin-label">Customer Reason</dt><dd class="mt-1 text-slate-600">{{ $orderReturn->reason }}</dd></div>
        @if($orderReturn->reviewed_at)
            <div><dt class="admin-label">Reviewed On</dt><dd class="mt-1">{{ $orderReturn->reviewed_at->format('M d, Y • g:i A') }}</dd></div>
            <div><dt class="admin-label">Reviewed By</dt><dd class="mt-1">{{ $orderReturn->reviewer?->name ?? '—' }}</dd></div>
        @endif
        @if($orderReturn->admin_notes)
            <div class="sm:col-span-2"><dt class="admin-label">Admin Notes</dt><dd class="mt-1 text-slate-600">{{ $orderReturn->admin_notes }}</dd></div>
        @endif
    </dl>

    @if($orderReturn->status->value === 'pending')
        <div class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
            <form action="{{ route('admin.order-returns.approve', $orderReturn) }}" method="POST">
                @csrf
                <label class="admin-label">Approval Notes (optional)</label>
                <textarea name="notes" class="admin-input mb-3" rows="2" maxlength="{{ config('admin.limits.notes') }}" placeholder="Refund reference, pickup details, etc."></textarea>
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Approve this return request?')">Approve Return</button>
            </form>
            <form action="{{ route('admin.order-returns.reject', $orderReturn) }}" method="POST">
                @csrf
                <label class="admin-label">Rejection Reason</label>
                <textarea name="reason" required class="admin-input mb-3" rows="2" maxlength="{{ config('admin.limits.reason') }}" placeholder="Reason for rejection"></textarea>
                <button type="submit" class="btn btn-sm bg-red-600 text-white" onclick="return confirm('Reject this return request?')">Reject Return</button>
            </form>
        </div>
    @endif

    <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
        <a href="{{ route('admin.order-returns.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
        @if($orderReturn->order)
            <a href="{{ route('admin.orders.show', $orderReturn->order) }}" class="btn btn-secondary btn-sm">View Order</a>
        @endif
    </div>
</div>
@endsection
