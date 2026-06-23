@extends('admin.layouts.app')
@section('title', $customer->name)
@section('page-title', 'Customer Details')
@section('page-subtitle', 'Account overview, orders, bookings and activity')

@section('content')
<div class="detail-header">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="user-cell">
            @if($customer->avatar)
                <img src="{{ asset('storage/'.$customer->avatar) }}" alt="" class="user-avatar !rounded-full !object-cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
            @endif
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $customer->name }}</h2>
                <p class="text-sm text-slate-500">{{ $customer->email ?? 'No email' }} · {{ $customer->mobile ?? 'No mobile' }}</p>
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

<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="detail-panel text-center">
        <p class="text-2xl font-bold text-slate-900">{{ $customer->orders_count }}</p>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Orders</p>
    </div>
    <div class="detail-panel text-center">
        <p class="text-2xl font-bold text-slate-900">{{ $customer->service_bookings_count }}</p>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bookings</p>
    </div>
    <div class="detail-panel text-center">
        <p class="text-2xl font-bold text-slate-900">{{ $customer->bulk_orders_count ?? 0 }}</p>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bulk Orders</p>
    </div>
    <div class="detail-panel text-center">
        <p class="text-2xl font-bold text-emerald-700">₹{{ number_format($customer->wallet_balance ?? 0, 2) }}</p>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Wallet</p>
    </div>
</div>

<div class="detail-grid">
    <div class="detail-panel">
        <h3 class="detail-panel-title">Profile Details</h3>
        <dl class="detail-dl">
            <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $customer->mobile ?? '—' }}</dd></div>
            <div><dt class="admin-label">Email</dt><dd class="mt-1">{{ $customer->email ?? '—' }}</dd></div>
            <div><dt class="admin-label">Role</dt><dd class="mt-1 capitalize">{{ $customer->role->value ?? $customer->role }}</dd></div>
            <div><dt class="admin-label">Joined</dt><dd class="mt-1">{{ $customer->created_at->format('M d, Y') }}</dd></div>
            <div class="span-full"><dt class="admin-label">Address</dt><dd class="mt-1 text-slate-600">{{ $customer->address ?? '—' }}</dd></div>
            @if($customer->is_blocked)
                <div class="span-full"><dt class="admin-label">Block Reason</dt><dd class="mt-1 text-red-600">{{ $customer->block_reason ?? '—' }}</dd></div>
            @endif
        </dl>
    </div>

    <div class="detail-panel">
        <h3 class="detail-panel-title">Order History</h3>
        @forelse($orders as $o)
            <a href="{{ route('admin.orders.show', $o) }}" class="detail-row hover:bg-slate-50 -mx-2 px-2 rounded-lg transition">
                <div>
                    <span class="font-medium text-slate-800">{{ $o->order_number }}</span>
                    <p class="text-xs text-slate-400">{{ $o->created_at->format('M d, Y') }}</p>
                </div>
                <div class="text-right">
                    <span class="font-semibold text-emerald-600">₹{{ number_format($o->total_amount, 2) }}</span>
                    <div class="mt-1">@include('admin.partials.status-badge', ['status' => $o->status])</div>
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400">No orders yet.</p>
        @endforelse
    </div>

    <div class="detail-panel lg:col-span-2">
        <h3 class="detail-panel-title">Booking History</h3>
        @forelse($bookings as $b)
            <a href="{{ route('admin.service-bookings.show', $b) }}" class="detail-row hover:bg-slate-50 -mx-2 px-2 rounded-lg transition">
                <div>
                    <span class="font-medium text-slate-800">{{ $b->service_name }}</span>
                    <p class="text-xs text-slate-400">{{ $b->booking_number }} · {{ $b->scheduled_at?->format('M d, Y') ?? 'No date' }}</p>
                    @if($b->serviceProvider)
                        <p class="text-xs text-slate-500">Provider: {{ $b->serviceProvider->name }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <span class="font-semibold text-emerald-600">₹{{ number_format($b->amount, 2) }}</span>
                    <div class="mt-1">@include('admin.partials.status-badge', ['status' => $b->status])</div>
                </div>
            </a>
        @empty
            <p class="text-sm text-slate-400">No bookings yet.</p>
        @endforelse
    </div>
</div>
@endsection
