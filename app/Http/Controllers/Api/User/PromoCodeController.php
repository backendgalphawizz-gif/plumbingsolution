<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\CouponAppliesTo;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    use ApiResponse;

    public function index(CouponService $coupons): JsonResponse
    {
        $items = $coupons->activeCoupons(CouponAppliesTo::Booking)
            ->map(fn ($coupon) => $coupons->formatUserCoupon($coupon));

        return $this->success($items);
    }

    public function apply(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'promo_code' => ['required', 'string', 'max:30'],
        ]);

        $service = Service::where('status', true)->findOrFail($data['service_id']);
        $result = $coupons->calculateForService($service, $data['promo_code']);

        if (! $result['coupon_applied']) {
            return $this->error('Invalid or inapplicable promo code.', 422);
        }

        return $this->success($result, 'Promo code applied.');
    }
}
