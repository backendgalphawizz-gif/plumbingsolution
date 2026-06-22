<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\BookingStatus;
use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ProviderEarningsService;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request, ProviderEarningsService $earnings): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $newBookings = $provider->bookings()
            ->with(['user', 'images', 'payment'])
            ->where('status', BookingStatus::Assigned)
            ->latest()
            ->limit(10)
            ->get();

        return $this->success([
            'total_earnings' => $earnings->totalEarnings($provider),
            'rating' => $earnings->averageRating($provider),
            'stats' => $earnings->bookingStats($provider),
            'new_bookings' => $newBookings->map(fn ($booking) => ProviderApiFormatter::booking($booking))->values(),
        ]);
    }
}
