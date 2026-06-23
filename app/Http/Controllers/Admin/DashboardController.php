<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Enums\VendorStatus;
use App\Http\Controllers\Admin\Concerns\BuildsChartSeries;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use BuildsChartSeries;

    public function index()
    {
        $stats = [
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
            'active_providers' => ServiceProvider::where('status', ProviderStatus::Approved)->count(),
            'pending_provider_approvals' => ServiceProvider::where('status', ProviderStatus::Pending)->count(),
        ];

        $chartStart = now()->subDays(29)->startOfDay();
        $chartEnd = now()->endOfDay();

        $dailySales = Order::whereBetween('created_at', [$chartStart, $chartEnd])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartSeries = $this->buildDateSeries($chartStart, $chartEnd, $dailySales);

        $pendingProviders = ServiceProvider::where('status', ProviderStatus::Pending)
            ->latest()
            ->limit(5)
            ->get();

        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'chartSeries', 'pendingProviders', 'recentOrders'));
    }
}
