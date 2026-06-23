<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Models\Payment;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;

class ProviderEarningsService
{
    public function __construct(private WalletService $wallet) {}

    public function totalEarnings(ServiceProvider $provider): float
    {
        return (float) ServiceBooking::query()
            ->where('service_provider_id', $provider->id)
            ->where('status', BookingStatus::Completed)
            ->sum('amount');
    }

    public function walletAmount(ServiceProvider $provider): float
    {
        $provider->loadMissing('user');

        return $provider->user
            ? $this->wallet->balance($provider->user)
            : 0;
    }

    public function averageRating(ServiceProvider $provider): float
    {
        $rating = ServiceProviderReview::query()
            ->where('service_provider_id', $provider->id)
            ->where('status', true)
            ->avg('rating');

        return round((float) $rating, 1);
    }

    public function bookingStats(ServiceProvider $provider): array
    {
        $base = ServiceBooking::query()->where('service_provider_id', $provider->id);

        return [
            'total_bookings' => (clone $base)->count(),
            'pending_bookings' => (clone $base)->whereIn('status', [BookingStatus::Pending, BookingStatus::Assigned])->count(),
            'completed_bookings' => (clone $base)->where('status', BookingStatus::Completed)->count(),
            'cancelled_bookings' => (clone $base)->where('status', BookingStatus::Cancelled)->count(),
        ];
    }

    public function transactionsQuery(ServiceProvider $provider)
    {
        return Payment::query()
            ->with('user')
            ->where('payable_type', ServiceBooking::class)
            ->whereIn('payable_id', ServiceBooking::query()
                ->where('service_provider_id', $provider->id)
                ->select('id'))
            ->latest();
    }
}
