<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function stats(): JsonResponse
    {
        return $this->success([
            'total_customers' => User::count(),
            'total_vendors' => Vendor::count(),
            'total_service_providers' => ServiceProvider::count(),
            'total_products' => Product::count(),
            'total_categories' => Category::count(),
            'total_orders' => Order::count(),
            'total_service_bookings' => ServiceBooking::count(),
            'total_revenue' => Payment::where('status', PaymentStatus::Completed)->sum('amount'),
            'total_pending_orders' => Order::where('status', OrderStatus::Pending)->count(),
            'total_pending_bookings' => ServiceBooking::where('status', BookingStatus::Pending)->count(),
            'active_vendors' => Vendor::where('status', VendorStatus::Approved)->count(),
            'active_providers' => ServiceProvider::where('status', ProviderStatus::Approved)->count(),
            'pending_vendor_approvals' => Vendor::where('status', VendorStatus::Pending)->count(),
            'pending_provider_approvals' => ServiceProvider::where('status', ProviderStatus::Pending)->count(),
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $period = $request->get('period', 'monthly');
        $months = $period === 'weekly' ? 4 : 12;

        $monthlySales = Order::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $monthlyRevenue = Payment::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
            DB::raw('SUM(amount) as revenue')
        )
            ->where('status', PaymentStatus::Completed)
            ->where('created_at', '>=', now()->subMonths($months))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $orderAnalytics = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->value ?? $item->status => $item->count]);

        $bookingAnalytics = ServiceBooking::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->status->value ?? $item->status => $item->count]);

        return $this->success([
            'monthly_sales' => $monthlySales,
            'monthly_revenue' => $monthlyRevenue,
            'orders_analytics' => $orderAnalytics,
            'booking_analytics' => $bookingAnalytics,
        ]);
    }

    public function recentActivity(): JsonResponse
    {
        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'order_number', 'user_id', 'status', 'total_amount', 'created_at']);

        $recentBookings = ServiceBooking::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get(['id', 'booking_number', 'user_id', 'service_name', 'status', 'created_at']);

        return $this->success([
            'recent_orders' => $recentOrders,
            'recent_bookings' => $recentBookings,
        ]);
    }
}
