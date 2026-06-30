<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CouponService;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponse;

    public function index(Request $request, CouponService $coupons): JsonResponse
    {
        return $this->success($this->buildCartResponse($request, $coupons));
    }

    public function store(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
        ]);

        $product = Product::where('status', true)->with('vendor')->findOrFail($data['product_id']);

        if ($product->stock < $data['quantity']) {
            return $this->error('Insufficient stock.', 422);
        }

        $user = $request->user();

        $existingVendorId = $user->cartItems()
            ->with('product')
            ->first()?->product?->vendor_id;

        $vendorSwitched = $existingVendorId && $existingVendorId !== $product->vendor_id;

        DB::transaction(function () use ($user, $product, $data, $vendorSwitched, $coupons) {
            if ($vendorSwitched) {
                $user->cartItems()->delete();
                $coupons->clearAppliedOrderCoupon($user);
            }

            CartItem::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $data['product_variant_id'] ?? null,
                ],
                ['quantity' => $data['quantity']]
            );
        });

        $message = $vendorSwitched
            ? 'Previous vendor items removed. Item added to cart.'
            : 'Item added to cart.';

        $response = $this->buildCartResponse($request, $coupons);
        $response['vendor_switched'] = $vendorSwitched;

        return $this->success($response, $message, 201);
    }

    public function update(Request $request, CartItem $cartItem, CouponService $coupons): JsonResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        if ($cartItem->product->stock < $data['quantity']) {
            return $this->error('Insufficient stock.', 422);
        }

        $cartItem->update(['quantity' => $data['quantity']]);

        return $this->success($this->buildCartResponse($request, $coupons), 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem, CouponService $coupons): JsonResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);
        $cartItem->delete();

        $user = $request->user();
        if (! $user->cartItems()->exists()) {
            $coupons->clearAppliedOrderCoupon($user);
        }

        return $this->success($this->buildCartResponse($request, $coupons), 'Item removed.');
    }

    public function applyCoupon(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required_without:coupon_code', 'nullable', 'string', 'max:30'],
            'coupon_code' => ['required_without:code', 'nullable', 'string', 'max:30'],
        ]);

        $code = trim((string) ($data['code'] ?? $data['coupon_code'] ?? ''));
        $user = $request->user();
        $cart = $this->buildCartResponse($request, $coupons, $code);

        if (! $cart['coupon_applied']) {
            return $this->error('Invalid or inapplicable coupon code.', 422);
        }

        $coupons->storeAppliedOrderCoupon($user, $cart['coupon_code']);

        return $this->success($cart, 'Coupon applied.');
    }

    public function removeCoupon(Request $request, CouponService $coupons): JsonResponse
    {
        $coupons->clearAppliedOrderCoupon($request->user());

        return $this->success($this->buildCartResponse($request, $coupons), 'Coupon removed.');
    }

    private function buildCartResponse(Request $request, CouponService $coupons, ?string $couponCode = null): array
    {
        $user = $request->user();

        $items = $user->cartItems()
            ->with([
                'product' => fn ($q) => $q->with(['vendor', 'images'])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->with(['reviews' => fn ($r) => $r->with('user')->latest()->limit(3)]),
            ])
            ->get();

        $couponCode ??= $coupons->appliedOrderCoupon($user);
        $pricing = $coupons->calculateForCart($user, $couponCode);
        $firstItem = $items->first();

        return [
            'vendor' => $firstItem ? UserApiFormatter::vendor($firstItem->product->vendor) : null,
            'items' => $items->map(fn ($i) => UserApiFormatter::cartItem($i)),
            'summary' => $pricing,
            'coupon_applied' => $pricing['coupon_applied'],
            'coupon_code' => $pricing['coupon_code'],
            'items_count' => $items->sum('quantity'),
        ];
    }
}
