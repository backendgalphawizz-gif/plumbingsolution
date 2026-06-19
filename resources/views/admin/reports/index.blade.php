@extends('admin.layouts.app')
@section('title', 'Reports')
@section('page-title', 'Sales Reports')
@section('page-subtitle', 'Revenue breakdown and order analytics')

@section('content')
<style>
.chart-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.chart-panel-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
.chart-panel-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
.chart-panel-sub { font-size: 0.8125rem; color: #64748b; margin-top: 2px; }
.chart-canvas-wrap { position: relative; height: 280px; }
.chart-canvas-donut { height: 220px; width: 220px; }
.chart-legend { display: flex; flex-wrap: wrap; gap: 16px; }
.chart-legend-item { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8125rem; font-weight: 600; color: #64748b; }
.chart-legend-dot { width: 10px; height: 10px; border-radius: 50%; }
.chart-stat-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-radius: 8px; background: #f8fafc; }
</style>

<div class="stat-tabs mb-6">
    @foreach(['daily','weekly','monthly','custom'] as $f)
        <a href="{{ request()->fullUrlWithQuery(['filter' => $f]) }}" class="stat-tab {{ $filter===$f ? 'active' : '' }}">{{ ucfirst($f) }}</a>
    @endforeach
</div>

@if($filter==='custom')
    @component('admin.partials.filter-panel')
        <div class="filter-field"><label class="admin-label">Start Date</label><input type="date" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}" class="admin-input"></div>
        <div class="filter-field"><label class="admin-label">End Date</label><input type="date" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}" class="admin-input"></div>
        <input type="hidden" name="filter" value="custom">
    @endcomponent
@endif

{{-- KPI Cards --}}
<div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="dash-stat">
        <div class="flex items-start justify-between">
            <div>
                <p class="dash-stat-label">Total Revenue</p>
                <p class="dash-stat-value text-emerald-600">₹{{ number_format($summary['revenue'], 0) }}</p>
                <p class="dash-stat-sub {{ $summary['revenueChange'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $summary['revenueChange'] >= 0 ? '↑' : '↓' }} {{ abs($summary['revenueChange']) }}% vs prev period
                </p>
            </div>
            <div class="dash-stat-icon bg-emerald-50 text-emerald-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="flex items-start justify-between">
            <div>
                <p class="dash-stat-label">Total Orders</p>
                <p class="dash-stat-value">{{ number_format($summary['totalOrders']) }}</p>
                <p class="dash-stat-sub {{ $summary['ordersChange'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                    {{ $summary['ordersChange'] >= 0 ? '↑' : '↓' }} {{ abs($summary['ordersChange']) }}% vs prev period
                </p>
            </div>
            <div class="dash-stat-icon bg-blue-50 text-blue-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="flex items-start justify-between">
            <div>
                <p class="dash-stat-label">Avg Order Value</p>
                <p class="dash-stat-value">₹{{ number_format($summary['avgOrderValue'], 0) }}</p>
                <p class="dash-stat-sub text-slate-400">Per completed order</p>
            </div>
            <div class="dash-stat-icon bg-violet-50 text-violet-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
        </div>
    </div>
    <div class="dash-stat">
        <div class="flex items-start justify-between">
            <div>
                <p class="dash-stat-label">Service Bookings</p>
                <p class="dash-stat-value">{{ number_format($summary['totalBookings']) }}</p>
                <p class="dash-stat-sub text-slate-400">{{ $start->format('M d') }} – {{ $end->format('M d, Y') }}</p>
            </div>
            <div class="dash-stat-icon bg-amber-50 text-amber-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
        </div>
    </div>
</div>

{{-- Main Revenue Chart --}}
<div class="chart-panel mb-6">
    <div class="chart-panel-header">
        <div>
            <h2 class="chart-panel-title">Revenue & Orders Trend</h2>
            <p class="chart-panel-sub">Daily performance across the selected period</p>
        </div>
        <div class="chart-legend">
            <span class="chart-legend-item"><span class="chart-legend-dot bg-emerald-500"></span> Revenue (₹)</span>
            <span class="chart-legend-item"><span class="chart-legend-dot bg-blue-500"></span> Orders</span>
        </div>
    </div>
    <div class="chart-canvas-wrap">
        <canvas id="revenueChart" height="120"></canvas>
    </div>
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    {{-- Orders by Status Donut --}}
    <div class="chart-panel">
        <div class="chart-panel-header">
            <div>
                <h2 class="chart-panel-title">Orders by Status</h2>
                <p class="chart-panel-sub">Distribution of order pipeline</p>
            </div>
            @if($ordersByStatus->isNotEmpty())
                <span class="badge badge-info">{{ $summary['totalOrders'] }} total</span>
            @endif
        </div>
        <div class="grid items-center gap-6 md:grid-cols-2">
            <div class="chart-canvas-wrap chart-canvas-donut mx-auto">
                <canvas id="ordersStatusChart"></canvas>
            </div>
            <div class="space-y-2">
                @forelse($ordersByStatus as $row)
                    @php $status = is_object($row->status) ? $row->status->value : $row->status; @endphp
                    <div class="chart-stat-row">
                        <span class="capitalize text-sm text-slate-600">{{ str_replace('_', ' ', $status) }}</span>
                        <div class="text-right">
                            <span class="font-bold text-slate-800">{{ $row->count }}</span>
                            <span class="text-xs text-slate-400">· ₹{{ number_format($row->total, 0) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No orders in this period.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Bookings by Status Bar --}}
    <div class="chart-panel">
        <div class="chart-panel-header">
            <div>
                <h2 class="chart-panel-title">Bookings by Status</h2>
                <p class="chart-panel-sub">Service booking breakdown</p>
            </div>
            @if($bookingsByStatus->isNotEmpty())
                <span class="badge badge-warning">{{ $summary['totalBookings'] }} total</span>
            @endif
        </div>
        <div class="chart-canvas-wrap">
            <canvas id="bookingsChart" height="140"></canvas>
        </div>
    </div>
</div>

{{-- Daily breakdown table --}}
@component('admin.partials.data-card', ['title' => 'Daily Breakdown', 'meta' => count($chartSeries['labels']).' days in period'])
    <table class="admin-table">
        <thead><tr><th>Date</th><th>Revenue</th><th>Orders</th><th>Share</th></tr></thead>
        <tbody>
            @php $maxRev = max(1, max($chartSeries['revenue'])); @endphp
            @foreach($chartSeries['labels'] as $i => $label)
                @if($chartSeries['revenue'][$i] > 0 || $chartSeries['orders'][$i] > 0)
                    <tr>
                        <td class="font-medium">{{ $label }}</td>
                        <td class="font-semibold text-emerald-600">₹{{ number_format($chartSeries['revenue'][$i], 2) }}</td>
                        <td>{{ $chartSeries['orders'][$i] }}</td>
                        <td>
                            <div class="flex items-center gap-2">
                                <div class="h-2 flex-1 max-w-[120px] rounded-full bg-slate-100">
                                    <div class="h-2 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600" style="width: {{ ($chartSeries['revenue'][$i] / $maxRev) * 100 }}%"></div>
                                </div>
                                <span class="text-xs text-slate-400">{{ round(($chartSeries['revenue'][$i] / max(1, $summary['revenue'])) * 100, 1) }}%</span>
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
@endcomponent

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function () {
    const font = "'Plus Jakarta Sans', system-ui, sans-serif";
    const gridColor = 'rgba(148, 163, 184, 0.15)';
    const tickColor = '#64748b';

    Chart.defaults.font.family = font;
    Chart.defaults.color = tickColor;
    Chart.defaults.animation.duration = 900;
    Chart.defaults.animation.easing = 'easeOutQuart';

    const series = @json($chartSeries);
    const statusChart = @json($statusChart);
    const bookingChart = @json($bookingChart);

    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        const gradient = revenueCtx.getContext('2d').createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, 'rgba(16, 185, 129, 0.35)');
        gradient.addColorStop(1, 'rgba(16, 185, 129, 0.02)');

        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: series.labels,
                datasets: [
                    {
                        label: 'Revenue (₹)',
                        data: series.revenue,
                        borderColor: '#10b981',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 2,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Orders',
                        data: series.orders,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.08)',
                        borderWidth: 2.5,
                        borderDash: [6, 4],
                        fill: false,
                        tension: 0.35,
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#3b82f6',
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { weight: '700', size: 13 },
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        callbacks: {
                            label(ctx) {
                                if (ctx.datasetIndex === 0) return ' ₹' + ctx.parsed.y.toLocaleString('en-IN');
                                return ' ' + ctx.parsed.y + ' orders';
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                    },
                    y: {
                        position: 'left',
                        grid: { color: gridColor },
                        ticks: {
                            callback: v => '₹' + v.toLocaleString('en-IN'),
                        },
                    },
                    y1: {
                        position: 'right',
                        grid: { drawOnChartArea: false },
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    }

    const ordersCtx = document.getElementById('ordersStatusChart');
    if (ordersCtx && statusChart.labels.length) {
        new Chart(ordersCtx, {
            type: 'doughnut',
            data: {
                labels: statusChart.labels,
                datasets: [{
                    data: statusChart.data,
                    backgroundColor: statusChart.backgrounds,
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 12,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '68%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 10,
                    },
                },
            },
        });
    } else if (ordersCtx) {
        ordersCtx.parentElement.innerHTML = '<p class="py-8 text-center text-sm text-slate-400">No order data</p>';
    }

    const bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx && bookingChart.labels.length) {
        new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: bookingChart.labels,
                datasets: [{
                    label: 'Bookings',
                    data: bookingChart.data,
                    backgroundColor: bookingChart.backgrounds.map(c => c + 'cc'),
                    borderColor: bookingChart.backgrounds,
                    borderWidth: 2,
                    borderRadius: 8,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 10,
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        grid: { color: gridColor },
                        ticks: { stepSize: 1 },
                    },
                },
            },
        });
    } else if (bookingsCtx) {
        bookingsCtx.parentElement.innerHTML = '<p class="py-8 text-center text-sm text-slate-400">No booking data</p>';
    }
})();
</script>
@endsection
