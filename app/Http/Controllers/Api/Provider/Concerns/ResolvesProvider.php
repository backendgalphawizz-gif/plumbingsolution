<?php

namespace App\Http\Controllers\Api\Provider\Concerns;

use App\Enums\ProviderStatus;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait ResolvesProvider
{
    protected function resolveProvider(Request $request): ?ServiceProvider
    {
        return $request->user()->serviceProvider;
    }

    protected function requireProvider(Request $request): ServiceProvider|JsonResponse
    {
        $provider = $this->resolveProvider($request);

        if (! $provider) {
            return $this->error('Provider profile not found.', 404);
        }

        return $provider;
    }

    protected function ensureApproved(ServiceProvider $provider): ?JsonResponse
    {
        if ($provider->status !== ProviderStatus::Approved) {
            return $this->error('Your provider account is pending admin approval.', 403);
        }

        return null;
    }

    protected function providerBooking(ServiceProvider $provider, int $bookingId): ?ServiceBooking
    {
        return $provider->bookings()
            ->with(['user', 'images', 'payment', 'review.user'])
            ->whereKey($bookingId)
            ->first();
    }
}
