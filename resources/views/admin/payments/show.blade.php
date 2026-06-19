@extends('admin.layouts.app')
@section('title', $payment->payment_id)
@section('page-title', 'Payment Details')
@section('page-subtitle', 'Transaction info and refund processing')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="form-card">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $payment->payment_id }}</h2>
            @include('admin.partials.status-badge', ['status' => $payment->status])
        </div>
        <dl class="space-y-3 text-sm">
            <div class="detail-row"><dt class="text-slate-500">Customer</dt><dd class="font-medium">{{ $payment->user?->name }}</dd></div>
            <div class="detail-row"><dt class="text-slate-500">Method</dt><dd class="capitalize">{{ $payment->method->value ?? $payment->method }}</dd></div>
            <div class="detail-row"><dt class="text-slate-500">Amount</dt><dd class="text-lg font-bold text-emerald-600">₹{{ number_format($payment->amount, 2) }}</dd></div>
            <div class="detail-row"><dt class="text-slate-500">Date</dt><dd>{{ $payment->created_at->format('M d, Y H:i') }}</dd></div>
        </dl>
    </div>
    @if($payment->status->value === 'completed')
        <form action="{{ route('admin.payments.refund', $payment) }}" method="POST" class="form-card">@csrf
            <div class="form-section-title text-red-600">Process Refund</div>
            <div class="form-section-desc">Partial or full refund for this transaction.</div>
            <div class="space-y-3">
                <input type="number" step="0.01" name="amount" max="{{ $payment->amount }}" required placeholder="Refund amount" class="admin-input">
                <textarea name="reason" placeholder="Reason for refund" maxlength="{{ config('admin.limits.reason') }}" class="admin-input" rows="2"></textarea>
            </div>
            <button class="btn btn-sm mt-4 bg-red-600 text-white">Process Refund</button>
        </form>
    @endif
</div>
@endsection
