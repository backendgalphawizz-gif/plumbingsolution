@extends('admin.layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard Overview')
@section('page-subtitle', 'Platform metrics and recent activity at a glance')

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('admin.customers.index') }}" class="dash-stat dash-link" title="View all customers">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Total Customers</p>
                    <p class="dash-stat-value">{{ number_format($stats['total_customers']) }}</p>
                </div>
                <div class="dash-stat-icon bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.service-providers.index') }}" class="dash-stat dash-link" title="View service providers">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Service Providers</p>
                    <p class="dash-stat-value">{{ number_format($stats['total_service_providers']) }}</p>
                    <p class="dash-stat-sub text-emerald-600">{{ number_format($stats['active_providers']) }} active</p>
                </div>
                <div class="dash-stat-icon bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.orders.index') }}" class="dash-stat dash-link" title="View all orders">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Total Orders</p>
                    <p class="dash-stat-value">{{ number_format($stats['total_orders']) }}</p>
                </div>
                <div class="dash-stat-icon bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
        </a>
        <a href="{{ route('admin.service-bookings.index', ['status' => 'pending']) }}" class="dash-stat dash-link" title="View pending bookings">
            <div class="flex items-start justify-between">
                <div>
                    <p class="dash-stat-label">Pending Bookings</p>
                    <p class="dash-stat-value">{{ number_format($stats['total_pending_bookings']) }}</p>
                    <p class="dash-stat-sub text-amber-600">{{ number_format($stats['total_pending_orders']) }} pending orders</p>
                </div>
                <div class="dash-stat-icon bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        @foreach([
            ['label' => 'Vendors', 'value' => $stats['total_vendors'], 'url' => route('admin.vendors.index')],
            ['label' => 'Products', 'value' => $stats['total_products'], 'url' => route('admin.products.index')],
            ['label' => 'Categories', 'value' => $stats['total_categories'], 'url' => route('admin.categories.index')],
            ['label' => 'Bookings', 'value' => $stats['total_service_bookings'], 'url' => route('admin.service-bookings.index')],
            ['label' => 'Revenue', 'value' => '₹'.number_format($stats['total_revenue'], 0), 'url' => route('admin.reports.index')],
            ['label' => 'Pending Approvals', 'value' => $stats['pending_provider_approvals'], 'url' => route('admin.service-providers.index', ['status' => 'pending'])],
        ] as $item)
            <a href="{{ $item['url'] }}" class="dash-mini dash-link" title="Go to {{ $item['label'] }}">
                <p class="dash-mini-label">{{ $item['label'] }}</p>
                <p class="dash-mini-value">{{ is_numeric($item['value']) ? number_format($item['value']) : $item['value'] }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2">
            @include('admin.partials.revenue-trend-chart', [
                'chartSeries' => $chartSeries,
                'canvasId' => 'dashboardRevenueChart',
                'title' => 'Revenue & Orders Trend',
                'subtitle' => 'Daily performance — last 30 days',
                'link' => route('admin.reports.index'),
                'linkLabel' => 'Full reports',
            ])
        </div>

        <div class="dash-panel">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="dash-panel-title">Recent Activity</h2>
                <a href="{{ route('admin.orders.index') }}" class="dash-view-all">View all</a>
            </div>
            <div class="space-y-2">
                @forelse($recentOrders as $order)
                    <a href="{{ route('admin.orders.show', $order) }}" class="dash-activity-link">
                        <div class="flex items-start gap-3">
                            <div class="user-avatar !h-8 !w-8 !rounded-lg !text-xs">₹</div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800">{{ $order->order_number }}</p>
                                <p class="text-xs text-slate-500">{{ $order->user->name ?? 'Customer' }} · {{ $order->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="text-sm font-bold text-emerald-600">₹{{ number_format($order->total_amount, 0) }}</span>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-400">No recent activity</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        @component('admin.partials.data-card', [
            'title' => 'Pending Provider Approvals',
            'meta' => $stats['pending_provider_approvals'].' awaiting review',
        ])
            @slot('actions')
                <a href="{{ route('admin.service-providers.index', ['status' => 'pending']) }}" class="dash-view-all">View all</a>
            @endslot
            <table class="admin-table">
                <thead><tr><th>Provider</th><th>Skills</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($pendingProviders as $provider)
                        <tr>
                            <td>
                                <a href="{{ route('admin.service-providers.show', $provider) }}" class="user-cell dash-link !rounded-lg">
                                    <div class="user-avatar !h-8 !w-8 !text-xs">{{ strtoupper(substr($provider->name, 0, 1)) }}</div>
                                    <div><div class="user-name">{{ $provider->name }}</div><div class="user-sub">{{ $provider->created_at->format('M d, Y') }}</div></div>
                                </a>
                            </td>
                            <td class="text-sm text-slate-500">{{ implode(', ', array_slice($provider->skills ?? ['General'], 0, 2)) }}</td>
                            <td>@include('admin.partials.status-badge', ['status' => 'pending'])</td>
                            <td><a href="{{ route('admin.service-providers.show', $provider) }}" class="action-btn">Review</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state"><p>No pending approvals</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        @endcomponent

        <a href="{{ route('admin.reports.index') }}" class="dash-panel dash-panel-link" title="View service performance reports">
            <h2 class="dash-panel-title mb-4">Top Performing Services</h2>
            @foreach([
                ['name' => 'Emergency Leak Repair', 'pct' => 85],
                ['name' => 'Heater Installation', 'pct' => 72],
                ['name' => 'Pipe Fitting', 'pct' => 58],
                ['name' => 'Drain Cleaning', 'pct' => 45],
            ] as $service)
                <div class="mb-4">
                    <div class="mb-1.5 flex justify-between text-sm">
                        <span class="font-medium text-slate-700">{{ $service['name'] }}</span>
                        <span class="font-bold text-slate-800">{{ $service['pct'] }}%</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-100">
                        <div class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400" style="width: {{ $service['pct'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </a>
    </div>
</div>
@endsection
