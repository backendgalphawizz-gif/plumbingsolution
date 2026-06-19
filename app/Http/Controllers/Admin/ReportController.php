<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        [$start, $end, $filter] = $this->range($request);

        $sales = Order::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $ordersByStatus = Order::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get();

        $bookingsByStatus = ServiceBooking::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        $revenue = (float) Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('amount');

        $totalOrders = (int) $sales->sum('count');
        $totalBookings = (int) $bookingsByStatus->sum('count');
        $avgOrderValue = $totalOrders > 0 ? $revenue / $totalOrders : 0;

        $periodDays = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $prevEnd = $start->copy()->subDay()->endOfDay();
        $prevStart = $prevEnd->copy()->subDays($periodDays - 1)->startOfDay();

        $prevRevenue = (float) Payment::whereBetween('created_at', [$prevStart, $prevEnd])
            ->where('status', 'completed')
            ->sum('amount');

        $prevOrders = (int) Order::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $revenueChange = $prevRevenue > 0 ? round((($revenue - $prevRevenue) / $prevRevenue) * 100, 1) : ($revenue > 0 ? 100 : 0);
        $ordersChange = $prevOrders > 0 ? round((($totalOrders - $prevOrders) / $prevOrders) * 100, 1) : ($totalOrders > 0 ? 100 : 0);

        $chartSeries = $this->buildDateSeries($start, $end, $sales);

        $statusChart = $this->buildStatusChart($ordersByStatus);
        $bookingChart = $this->buildStatusChart($bookingsByStatus, [
            'pending' => '#f59e0b',
            'assigned' => '#3b82f6',
            'accepted' => '#6366f1',
            'started' => '#8b5cf6',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
        ]);

        $summary = compact(
            'revenue', 'totalOrders', 'totalBookings', 'avgOrderValue',
            'revenueChange', 'ordersChange', 'prevRevenue', 'prevOrders'
        );

        return view('admin.reports.index', compact(
            'sales', 'ordersByStatus', 'bookingsByStatus', 'revenue', 'filter', 'start', 'end',
            'chartSeries', 'statusChart', 'bookingChart', 'summary'
        ));
    }

    private function range(Request $request): array
    {
        $filter = $request->get('filter', 'monthly');

        $range = match ($filter) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->get('start_date', now()->startOfMonth()))->startOfDay(),
                Carbon::parse($request->get('end_date', now()->endOfMonth()))->endOfDay(),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };

        return [...$range, $filter];
    }

    private function buildDateSeries(Carbon $start, Carbon $end, Collection $sales): array
    {
        $map = $sales->keyBy(fn ($row) => Carbon::parse($row->date)->format('Y-m-d'));
        $labels = [];
        $revenue = [];
        $orders = [];

        $current = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($current <= $endDay) {
            $key = $current->format('Y-m-d');
            $row = $map->get($key);
            $labels[] = $current->format('M d');
            $revenue[] = round((float) ($row->total ?? 0), 2);
            $orders[] = (int) ($row->count ?? 0);
            $current->addDay();
        }

        return compact('labels', 'revenue', 'orders');
    }

    private function buildStatusChart(Collection $rows, array $colorMap = []): array
    {
        $defaultColors = [
            'pending' => '#f59e0b',
            'accepted' => '#3b82f6',
            'approved' => '#10b981',
            'packed' => '#6366f1',
            'shipped' => '#8b5cf6',
            'delivered' => '#059669',
            'completed' => '#10b981',
            'cancelled' => '#ef4444',
            'rejected' => '#dc2626',
            'refunded' => '#64748b',
            'returned' => '#f97316',
            'assigned' => '#0ea5e9',
            'started' => '#a855f7',
            'inactive' => '#94a3b8',
            'active' => '#10b981',
        ];

        $colors = array_merge($defaultColors, $colorMap);
        $palette = ['#10b981', '#3b82f6', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4', '#ec4899', '#84cc16'];

        $labels = [];
        $data = [];
        $backgrounds = [];
        $i = 0;

        foreach ($rows as $row) {
            $status = is_object($row->status) ? $row->status->value : (string) $row->status;
            $labels[] = ucfirst(str_replace('_', ' ', $status));
            $data[] = (int) $row->count;
            $backgrounds[] = $colors[$status] ?? $palette[$i % count($palette)];
            $i++;
        }

        return compact('labels', 'data', 'backgrounds');
    }
}
