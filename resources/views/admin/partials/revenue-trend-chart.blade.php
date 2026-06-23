@once
<style>
.chart-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.chart-panel-header { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
.chart-panel-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
.chart-panel-sub { font-size: 0.8125rem; color: #64748b; margin-top: 2px; }
.chart-canvas-wrap { position: relative; height: 280px; }
.chart-legend { display: flex; flex-wrap: wrap; gap: 16px; }
.chart-legend-item { display: inline-flex; align-items: center; gap: 6px; font-size: 0.8125rem; font-weight: 600; color: #64748b; }
.chart-legend-dot { width: 10px; height: 10px; border-radius: 50%; }
</style>
@endonce

@props([
    'chartSeries',
    'canvasId' => 'revenueChart',
    'title' => 'Revenue & Orders Trend',
    'subtitle' => 'Daily performance across the selected period',
    'link' => null,
    'linkLabel' => 'Full reports',
    'class' => '',
])

<div class="chart-panel {{ $class }}">
    <div class="chart-panel-header">
        <div>
            <h2 class="chart-panel-title">{{ $title }}</h2>
            <p class="chart-panel-sub">{{ $subtitle }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <div class="chart-legend">
                <span class="chart-legend-item"><span class="chart-legend-dot bg-emerald-500"></span> Revenue (₹)</span>
                <span class="chart-legend-item"><span class="chart-legend-dot bg-blue-500"></span> Orders</span>
            </div>
            @if($link)
                <a href="{{ $link }}" class="dash-view-all">{{ $linkLabel }}</a>
            @endif
        </div>
    </div>
    <div class="chart-canvas-wrap">
        <canvas id="{{ $canvasId }}" height="120"></canvas>
    </div>
</div>

@once
    @prepend('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    @endprepend
@endonce

@push('scripts')
<script>
(function () {
    const canvasId = @json($canvasId);
    const series = @json($chartSeries);
    const ctx = document.getElementById(canvasId);
    if (!ctx || typeof Chart === 'undefined') return;

    const font = "'Plus Jakarta Sans', system-ui, sans-serif";
    const gridColor = 'rgba(148, 163, 184, 0.15)';
    const tickColor = '#64748b';

    Chart.defaults.font.family = font;
    Chart.defaults.color = tickColor;
    Chart.defaults.animation.duration = 900;
    Chart.defaults.animation.easing = 'easeOutQuart';

    const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(60, 180, 242, 0.35)');
    gradient.addColorStop(1, 'rgba(60, 180, 242, 0.02)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: series.labels,
            datasets: [
                {
                    label: 'Revenue (₹)',
                    data: series.revenue,
                    borderColor: '#3cb4f2',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#3cb4f2',
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
                        label(c) {
                            if (c.datasetIndex === 0) return ' ₹' + c.parsed.y.toLocaleString('en-IN');
                            return ' ' + c.parsed.y + ' orders';
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
})();
</script>
@endpush
