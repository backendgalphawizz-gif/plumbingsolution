<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductReview;
use App\Models\Vendor;

class VendorEarningsService
{
    public function __construct(private WalletService $wallet) {}

    public function totalEarnings(Vendor $vendor): float
    {
        return (float) Order::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', OrderStatus::Delivered)
            ->sum('total_amount');
    }

    public function walletAmount(Vendor $vendor): float
    {
        $vendor->loadMissing('user');

        return $vendor->user
            ? $this->wallet->balance($vendor->user)
            : 0;
    }

    public function averageRating(Vendor $vendor): float
    {
        $rating = ProductReview::query()
            ->where('status', true)
            ->whereHas('product', fn ($q) => $q->where('vendor_id', $vendor->id))
            ->avg('rating');

        return round((float) $rating, 1);
    }

    public function orderStats(Vendor $vendor): array
    {
        $base = Order::query()->where('vendor_id', $vendor->id);

        return [
            'total_orders' => (clone $base)->count(),
            'pending_orders' => (clone $base)->where('status', OrderStatus::Pending)->count(),
            'completed_orders' => (clone $base)->where('status', OrderStatus::Delivered)->count(),
            'cancelled_orders' => (clone $base)->where('status', OrderStatus::Cancelled)->count(),
        ];
    }

    public function transactionsQuery(Vendor $vendor)
    {
        return Payment::query()
            ->with('user')
            ->where('payable_type', Order::class)
            ->whereIn('payable_id', Order::query()->where('vendor_id', $vendor->id)->select('id'))
            ->latest();
    }
}
