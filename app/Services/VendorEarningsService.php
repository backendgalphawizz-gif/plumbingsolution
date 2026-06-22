<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductReview;
use App\Models\Vendor;
use App\Models\VendorWithdrawal;

class VendorEarningsService
{
    public function totalEarnings(Vendor $vendor): float
    {
        return (float) Order::query()
            ->where('vendor_id', $vendor->id)
            ->where('status', OrderStatus::Delivered)
            ->sum('total_amount');
    }

    public function walletAmount(Vendor $vendor): float
    {
        $earnings = $this->totalEarnings($vendor);
        $locked = (float) VendorWithdrawal::query()
            ->where('vendor_id', $vendor->id)
            ->whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Paid])
            ->sum('amount');

        return max(0, $earnings - $locked);
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
