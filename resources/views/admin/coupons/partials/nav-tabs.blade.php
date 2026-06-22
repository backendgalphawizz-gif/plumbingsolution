@props(['active' => 'order', 'orderCount' => 0, 'bookingCount' => 0])

<div class="page-toolbar">
    <div class="stat-tabs">
        <a href="{{ route('admin.coupons.order.index') }}" class="stat-tab {{ $active === 'order' ? 'active' : '' }}">
            Order Coupons
            <span class="count">{{ $orderCount }}</span>
        </a>
        <a href="{{ route('admin.coupons.booking.index') }}" class="stat-tab {{ $active === 'booking' ? 'active' : '' }}">
            Booking Coupons
            <span class="count">{{ $bookingCount }}</span>
        </a>
    </div>
    {{ $slot }}
</div>
