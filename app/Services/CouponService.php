<?php

namespace App\Services;

use App\Enums\CouponAppliesTo;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Collection;

class CouponService
{
    public function find(string $code, CouponAppliesTo $appliesTo): ?Coupon
    {
        return Coupon::where('code', strtoupper($code))
            ->where('applies_to', $appliesTo->value)
            ->first();
    }

    public function activeCoupons(CouponAppliesTo $appliesTo): Collection
    {
        return Coupon::where('applies_to', $appliesTo->value)
            ->where('status', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('code')
            ->get();
    }

    public function formatCoupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'applies_to' => $coupon->applies_to instanceof CouponAppliesTo
                ? $coupon->applies_to->value
                : $coupon->applies_to,
            'discount_type' => $coupon->discount_type,
            'discount_value' => (float) $coupon->discount_value,
            'min_order_amount' => (float) $coupon->min_order_amount,
            'status' => (bool) $coupon->status,
            'expires_at' => $coupon->expires_at?->format('Y-m-d'),
            'created_at' => $coupon->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function formatUserCoupon(Coupon $coupon, ?float $subtotal = null): array
    {
        $data = $this->formatCoupon($coupon);
        $data['title'] = $this->couponTitle($coupon);
        $data['description'] = $this->couponDescription($coupon);
        $data['is_applicable'] = $subtotal !== null ? $coupon->isValidFor($subtotal) : null;
        $data['estimated_discount'] = ($subtotal !== null && $data['is_applicable'])
            ? $coupon->calculateDiscount($subtotal)
            : null;

        return $data;
    }

    public function cartSubtotalForUser(User $user): float
    {
        return (float) $user->cartItems()
            ->with('product')
            ->get()
            ->sum(fn ($item) => (float) ($item->product->sale_price ?? $item->product->price) * $item->quantity);
    }

    private function couponTitle(Coupon $coupon): string
    {
        if ($coupon->discount_type === 'percent') {
            $value = rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.');

            return "{$value}% OFF";
        }

        return '₹'.number_format((float) $coupon->discount_value, 0).' OFF';
    }

    private function couponDescription(Coupon $coupon): string
    {
        $parts = [];

        if ((float) $coupon->min_order_amount > 0) {
            $parts[] = 'Min. order ₹'.number_format((float) $coupon->min_order_amount, 0);
        }

        if ($coupon->expires_at) {
            $parts[] = 'Valid till '.$coupon->expires_at->format('M d, Y');
        }

        return $parts !== [] ? implode(' · ', $parts) : 'No minimum order';
    }

    public function calculateForAmount(float $subtotal, ?string $couponCode, CouponAppliesTo $appliesTo): array
    {
        $discount = 0;
        $couponApplied = false;
        $couponData = null;
        $resolvedCode = null;

        if ($couponCode) {
            $coupon = $this->find($couponCode, $appliesTo);

            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
                $couponApplied = true;
                $couponData = $this->formatCoupon($coupon);
                $resolvedCode = $coupon->code;
            }
        }

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'total' => round(max(0, $subtotal - $discount), 2),
            'coupon_applied' => $couponApplied,
            'coupon_code' => $resolvedCode,
            'coupon' => $couponData,
        ];
    }

    public function calculateForService(Service $service, ?string $couponCode): array
    {
        $result = $this->calculateForAmount(
            (float) $service->starting_price,
            $couponCode,
            CouponAppliesTo::Booking
        );

        return array_merge([
            'service_id' => $service->id,
            'service_name' => $service->name,
        ], $result);
    }
}
