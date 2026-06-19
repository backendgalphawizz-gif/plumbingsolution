<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        [$start, $end] = $this->dateRange($request);

        return $this->success([
            'sales' => $this->salesReport($start, $end),
            'orders' => $this->ordersReport($start, $end),
            'bookings' => $this->bookingsReport($start, $end),
            'vendors' => $this->vendorsReport($start, $end),
            'providers' => $this->providersReport($start, $end),
            'revenue' => $this->revenueReport($start, $end),
        ]);
    }

    private function dateRange(Request $request): array
    {
        $filter = $request->get('filter', 'monthly');

        return match ($filter) {
            'daily' => [now()->startOfDay(), now()->endOfDay()],
            'weekly' => [now()->startOfWeek(), now()->endOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'custom' => [
                Carbon::parse($request->get('start_date', now()->startOfMonth())),
                Carbon::parse($request->get('end_date', now()->endOfMonth())),
            ],
            default => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    private function salesReport(Carbon $start, Carbon $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function ordersReport(Carbon $start, Carbon $end): array
    {
        return Order::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    private function bookingsReport(Carbon $start, Carbon $end): array
    {
        return ServiceBooking::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    private function vendorsReport(Carbon $start, Carbon $end): array
    {
        return Vendor::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    private function providersReport(Carbon $start, Carbon $end): array
    {
        return ServiceProvider::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    private function revenueReport(Carbon $start, Carbon $end): array
    {
        return Payment::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
