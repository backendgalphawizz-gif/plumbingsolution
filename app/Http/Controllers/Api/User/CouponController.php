<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\CouponAppliesTo;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function index(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'applies_to' => ['nullable', 'in:order,booking'],
        ]);

        $appliesTo = CouponAppliesTo::from($data['applies_to'] ?? CouponAppliesTo::Order->value);
        $subtotal = $appliesTo === CouponAppliesTo::Order
            ? $coupons->cartSubtotalForUser($request->user())
            : null;

        $items = $coupons->activeCoupons($appliesTo)
            ->map(fn ($coupon) => $coupons->formatUserCoupon($coupon, $subtotal));

        return $this->success($items);
    }
}
