@extends('admin.layouts.app')
@section('title', $withdrawal->transaction_id)
@section('page-title', 'Withdrawal Details')
@section('page-subtitle', ucfirst($type).' payout request')

@section('content')
@php
    $name = match ($type) {
        'provider' => $withdrawal->serviceProvider?->name,
        'user' => $withdrawal->user?->name,
        default => $withdrawal->vendor?->shop_name,
    };
    $mobile = match ($type) {
        'provider' => $withdrawal->serviceProvider?->mobile,
        'user' => $withdrawal->user?->mobile,
        default => $withdrawal->vendor?->mobile,
    };
    $maskedAccount = $withdrawal->account_number
        ? str_repeat('•', max(0, strlen($withdrawal->account_number) - 4)).substr($withdrawal->account_number, -4)
        : '—';
@endphp

<div class="form-card mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold text-slate-900">{{ $withdrawal->transaction_id }}</h2>
            <p class="text-sm text-slate-500">{{ ucfirst($type) }} withdrawal</p>
        </div>
        @include('admin.partials.status-badge', ['status' => $withdrawal->status])
    </div>

    <dl class="grid gap-4 text-sm sm:grid-cols-2">
        <div><dt class="admin-label">Name</dt><dd class="mt-1 font-medium">{{ $name ?: '—' }}</dd></div>
        <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $mobile ?: '—' }}</dd></div>
        <div><dt class="admin-label">Amount</dt><dd class="mt-1 text-lg font-bold text-emerald-700">₹{{ number_format($withdrawal->amount, 2) }}</dd></div>
        <div><dt class="admin-label">Requested On</dt><dd class="mt-1">{{ $withdrawal->created_at->format('M d, Y • g:i A') }}</dd></div>
        <div><dt class="admin-label">Bank</dt><dd class="mt-1">{{ $withdrawal->bank_name ?: '—' }}</dd></div>
        <div><dt class="admin-label">IFSC</dt><dd class="mt-1">{{ $withdrawal->ifsc_code ?: '—' }}</dd></div>
        <div><dt class="admin-label">Account Number</dt><dd class="mt-1 font-mono">{{ $maskedAccount }}</dd></div>
        @if($type === 'user' && $withdrawal->account_holder_name)
            <div><dt class="admin-label">Account Holder</dt><dd class="mt-1">{{ $withdrawal->account_holder_name }}</dd></div>
        @endif
        @if($withdrawal->processed_at)
            <div><dt class="admin-label">Processed On</dt><dd class="mt-1">{{ $withdrawal->processed_at->format('M d, Y • g:i A') }}</dd></div>
        @endif
        @if($withdrawal->notes)
            <div class="sm:col-span-2"><dt class="admin-label">Notes</dt><dd class="mt-1 text-slate-600">{{ $withdrawal->notes }}</dd></div>
        @endif
    </dl>

    @if($withdrawal->status->value === 'pending')
        <div class="mt-6 grid gap-4 border-t border-slate-100 pt-5 sm:grid-cols-2">
            <form action="{{ route('admin.withdrawals.approve', ['type' => $type, 'withdrawal' => $withdrawal->id]) }}" method="POST">
                @csrf
                <label class="admin-label">Approval Notes (optional)</label>
                <textarea name="notes" class="admin-input mb-3" rows="2" maxlength="{{ config('admin.limits.notes') }}" placeholder="Payment reference, UTR, etc."></textarea>
                <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Mark this withdrawal as paid?')">Mark as Paid</button>
            </form>
            <form action="{{ route('admin.withdrawals.reject', ['type' => $type, 'withdrawal' => $withdrawal->id]) }}" method="POST">
                @csrf
                <label class="admin-label">Rejection Reason</label>
                <textarea name="reason" required class="admin-input mb-3" rows="2" maxlength="{{ config('admin.limits.reason') }}" placeholder="Reason for rejection"></textarea>
                <button type="submit" class="btn btn-sm bg-red-600 text-white" onclick="return confirm('Reject this withdrawal request?')">Reject</button>
            </form>
        </div>
    @endif

    <div class="mt-6 border-t border-slate-100 pt-5">
        <a href="{{ route('admin.withdrawals.index', ['type' => $type]) }}" class="btn btn-secondary btn-sm">Back to list</a>
    </div>
</div>
@endsection
