<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Enums\VendorStatus;
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

        $monthlyRevenue = Order::select(
            DB::raw('DATE_FORMAT(created_at, "%b") as month'),
            DB::raw('SUM(total_amount) as revenue')
        )
            ->where('created_at', '>=', now()->subMonths(4))
            ->groupBy('month')
            ->orderByRaw('MIN(created_at)')
            ->get();

        $pendingProviders = ServiceProvider::where('status', ProviderStatus::Pending)
            ->latest()
            ->limit(5)
            ->get();

        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'monthlyRevenue', 'pendingProviders', 'recentOrders'));
    }
}
