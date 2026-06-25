@extends('admin.layouts.app')

@php
    $isOrder = $type === \App\Enums\CouponAppliesTo::Order;
    $activeTab = $isOrder ? 'order' : 'booking';
    $title = $isOrder ? 'Order Coupons' : 'Booking Coupons';
    $subtitle = $isOrder
        ? 'Manage promo codes for product cart and checkout'
        : 'Manage promo codes for service bookings';
    $storeRoute = $isOrder ? route('admin.coupons.order.store') : route('admin.coupons.booking.store');
@endphp

@section('title', 'Coupon Management')
@section('page-title', 'Coupon Management')
@section('page-subtitle', $subtitle)

@section('content')
<div x-data="{ openCoupon: null, editCoupon: null }">
    @component('admin.coupons.partials.nav-tabs', [
        'active' => $activeTab,
        'orderCount' => $counts['order'],
        'bookingCount' => $counts['booking'],
    ])
        <button type="button" @click="openCoupon = 'new'" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add {{ $title }}
        </button>
    @endcomponent

    @component('admin.partials.filter-panel')
        <div class="filter-field">
            <label class="admin-label">Search</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Coupon code..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
        </div>
        <div class="filter-field">
            <label class="admin-label">Discount Type</label>
            <select name="discount_type" class="admin-input">
                <option value="">All types</option>
                <option value="percent" @selected(request('discount_type') === 'percent')>Percentage</option>
                <option value="fixed" @selected(request('discount_type') === 'fixed')>Fixed amount</option>
            </select>
        </div>
        <div class="filter-field">
            <label class="admin-label">Status</label>
            <select name="status" class="admin-input">
                <option value="">All statuses</option>
                <option value="1" @selected(request('status') === '1')>Active</option>
                <option value="0" @selected(request('status') === '0')>Inactive</option>
            </select>
        </div>
        @include('admin.partials.date-filters')
    @endcomponent

    @component('admin.partials.data-card', [
        'title' => $title,
        'meta' => number_format($coupons->total()).' coupons found',
    ])
        <table class="admin-table">
            <thead><tr>
                <th>Code</th>
                <th>Discount</th>
                <th>Min Amount</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td><span class="font-semibold tracking-wide text-slate-800">{{ $coupon->code }}</span></td>
                        <td>
                            @if($coupon->discount_type === 'percent')
                                <span class="font-semibold text-emerald-600">{{ rtrim(rtrim(number_format($coupon->discount_value, 2), '0'), '.') }}%</span>
                            @else
                                <span class="font-semibold text-emerald-600">₹{{ number_format($coupon->discount_value, 2) }}</span>
                            @endif
                        </td>
                        <td class="text-sm text-slate-500">₹{{ number_format($coupon->min_order_amount, 2) }}</td>
                        <td class="text-sm text-slate-500">
                            @if($coupon->expires_at)
                                {{ $coupon->expires_at->format('M d, Y') }}
                                @if($coupon->expires_at->isPast())
                                    <span class="ml-1 text-xs font-semibold text-red-500">Expired</span>
                                @endif
                            @else
                                No expiry
                            @endif
                        </td>
                        <td>@include('admin.partials.status-badge', ['status' => $coupon->status ? 'active' : 'inactive'])</td>
                        <td class="text-sm text-slate-500">{{ $coupon->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="action-group">
                                <button @click="editCoupon = {{ $coupon->id }}" class="action-btn">Edit</button>
                                <form action="{{ $isOrder ? route('admin.coupons.order.destroy', $coupon) : route('admin.coupons.booking.destroy', $coupon) }}" method="POST" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <p>No {{ strtolower($title) }} yet. Create your first coupon.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @slot('footer'){{ $coupons->links() }}@endslot
    @endcomponent

    @foreach($coupons as $coupon)
        <div x-show="editCoupon === {{ $coupon->id }}" x-cloak class="modal-backdrop">
            <div @click.outside="editCoupon = null" class="modal-card">
                <h3 class="modal-title">Edit {{ $title }}</h3>
                <form action="{{ $isOrder ? route('admin.coupons.order.update', $coupon) : route('admin.coupons.booking.update', $coupon) }}" method="POST" class="space-y-4">
                    @csrf @method('PUT')
                    @include('admin.coupons.partials.coupon-fields', ['coupon' => $coupon])
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editCoupon = null" class="btn btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div x-show="openCoupon === 'new'" x-cloak class="modal-backdrop">
        <div @click.outside="openCoupon = null" class="modal-card">
            <h3 class="modal-title">Add {{ $title }}</h3>
            <form action="{{ $storeRoute }}" method="POST" class="space-y-4">
                @csrf
                @include('admin.coupons.partials.coupon-fields', ['coupon' => null])
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCoupon = null" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
