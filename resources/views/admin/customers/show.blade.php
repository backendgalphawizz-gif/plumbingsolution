@extends('admin.layouts.app')
@section('title', $customer->name)
@section('page-title', 'Customer Details')
@section('page-subtitle', 'Account overview, orders and bookings')

@section('content')
<div class="detail-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="user-cell">
            <div class="user-avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $customer->name }}</h2>
                <p class="text-sm text-slate-500">{{ $customer->email }} · {{ $customer->mobile ?? 'No mobile' }}</p>
                <div class="mt-2">@include('admin.partials.status-badge', ['status' => $customer->is_blocked ? 'inactive' : 'active'])</div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-secondary btn-sm">Edit Customer</a>
            @if($customer->is_blocked)
                <form action="{{ route('admin.customers.unblock', $customer) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">Unblock</button></form>
            @else
                <form action="{{ route('admin.customers.block', $customer) }}" method="POST" class="flex flex-wrap items-center gap-2">@csrf
                    <input name="reason" placeholder="Block reason" required maxlength="{{ config('admin.limits.reason') }}" class="admin-input !h-[34px] max-w-xs">
                    <button class="btn btn-sm bg-red-600 text-white hover:bg-red-700">Block</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-panel">
        <h3 class="detail-panel-title">Order History</h3>
        @forelse($orders as $o)
            <div class="detail-row"><span class="font-medium">{{ $o->order_number }}</span><span class="font-semibold text-emerald-600">₹{{ number_format($o->total_amount, 2) }}</span></div>
        @empty
            <p class="text-sm text-slate-400">No orders yet.</p>
        @endforelse
    </div>
    <div class="detail-panel">
        <h3 class="detail-panel-title">Booking History</h3>
        @forelse($bookings as $b)
            <div class="detail-row"><span>{{ $b->service_name }}</span>@include('admin.partials.status-badge', ['status' => $b->status])</div>
        @empty
            <p class="text-sm text-slate-400">No bookings yet.</p>
        @endforelse
    </div>
</div>
@endsection
