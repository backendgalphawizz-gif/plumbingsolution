<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\CouponAppliesTo;
use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use App\Models\ServiceProvider;
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
            'service_provider_id' => ['nullable', 'exists:service_providers,id'],
            'promo_code' => ['required_without_all:coupon_code,code', 'nullable', 'string', 'max:30'],
            'coupon_code' => ['required_without_all:promo_code,code', 'nullable', 'string', 'max:30'],
            'code' => ['required_without_all:promo_code,coupon_code', 'nullable', 'string', 'max:30'],
        ]);

        $service = Service::where('status', true)->findOrFail($data['service_id']);

        $provider = null;
        if (! empty($data['service_provider_id'])) {
            $provider = ServiceProvider::where('status', ProviderStatus::Approved)
                ->where('id', $data['service_provider_id'])
                ->firstOrFail();

            if (! $provider->services()->where('services.id', $service->id)->exists()) {
                return $this->error('Selected provider does not offer this service.', 422);
            }
        }

        $promoCode = trim((string) ($data['promo_code'] ?? $data['coupon_code'] ?? $data['code'] ?? ''));
        $result = $coupons->calculateForService($service, $promoCode, $provider);

        if (! $result['coupon_applied']) {
            return $this->error('Invalid or inapplicable promo code.', 422);
        }

        $coupons->storeAppliedBookingCoupon($request->user(), $service->id, $result['coupon_code']);

        return $this->success($result, 'Promo code applied.');
    }
}
