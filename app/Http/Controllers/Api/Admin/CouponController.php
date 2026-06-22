<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\CouponAppliesTo;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $appliesTo): JsonResponse
    {
        $type = CouponAppliesTo::from($appliesTo);

        $coupons = Coupon::where('applies_to', $type)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Coupon $coupon) => app(CouponService::class)->formatCoupon($coupon));

        return $this->success($coupons);
    }

    public function store(Request $request, string $appliesTo): JsonResponse
    {
        $type = CouponAppliesTo::from($appliesTo);
        $data = $this->validated($request, $type);
        $data['code'] = strtoupper($data['code']);
        $data['applies_to'] = $type->value;

        $coupon = Coupon::create($data);

        return $this->success(
            app(CouponService::class)->formatCoupon($coupon),
            ucfirst($type->value).' coupon created.',
            201
        );
    }

    public function show(string $appliesTo, Coupon $coupon): JsonResponse
    {
        $type = CouponAppliesTo::from($appliesTo);
        $this->ensureType($coupon, $type);

        return $this->success(app(CouponService::class)->formatCoupon($coupon));
    }

    public function update(Request $request, string $appliesTo, Coupon $coupon): JsonResponse
    {
        $type = CouponAppliesTo::from($appliesTo);
        $this->ensureType($coupon, $type);

        $data = $this->validated($request, $type, $coupon);
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);

        return $this->success(
            app(CouponService::class)->formatCoupon($coupon->fresh()),
            ucfirst($type->value).' coupon updated.'
        );
    }

    public function destroy(string $appliesTo, Coupon $coupon): JsonResponse
    {
        $type = CouponAppliesTo::from($appliesTo);
        $this->ensureType($coupon, $type);
        $coupon->delete();

        return $this->success(null, ucfirst($type->value).' coupon deleted.');
    }

    private function validated(Request $request, CouponAppliesTo $appliesTo, ?Coupon $coupon = null): array
    {
        $codeRule = Rule::unique('coupons', 'code')
            ->where('applies_to', $appliesTo->value);

        if ($coupon) {
            $codeRule->ignore($coupon->id);
        }

        return $request->validate([
            'code' => [$coupon ? 'sometimes' : 'required', 'string', 'max:30', $codeRule],
            'discount_type' => [$coupon ? 'sometimes' : 'required', 'in:fixed,percent'],
            'discount_value' => [$coupon ? 'sometimes' : 'required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);
    }

    private function ensureType(Coupon $coupon, CouponAppliesTo $appliesTo): void
    {
        $type = $coupon->applies_to instanceof CouponAppliesTo
            ? $coupon->applies_to
            : CouponAppliesTo::from($coupon->applies_to);

        abort_if($type !== $appliesTo, 404);
    }
}
