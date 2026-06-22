<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\VendorEarningsService;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function index(Request $request, VendorEarningsService $earnings): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $newOrders = $vendor->orders()
            ->with(['user', 'items.product.images', 'payment'])
            ->where('status', OrderStatus::Pending)
            ->latest()
            ->limit(10)
            ->get();

        return $this->success([
            'total_earnings' => $earnings->totalEarnings($vendor),
            'rating' => $earnings->averageRating($vendor),
            'stats' => $earnings->orderStats($vendor),
            'new_orders' => $newOrders->map(fn ($order) => VendorApiFormatter::order($order))->values(),
        ]);
    }
}
