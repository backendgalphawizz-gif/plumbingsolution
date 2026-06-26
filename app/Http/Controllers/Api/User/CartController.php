<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Enums\CouponAppliesTo;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CouponService;
use App\Services\TaxService;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        return $this->success($this->buildCartResponse($request));
    }

    public function store(Request $request): JsonResponse
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

        DB::transaction(function () use ($user, $product, $data, $vendorSwitched) {
            if ($vendorSwitched) {
                $user->cartItems()->delete();
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

        $response = $this->buildCartResponse($request);
        $response['vendor_switched'] = $vendorSwitched;

        return $this->success($response, $message, 201);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        if ($cartItem->product->stock < $data['quantity']) {
            return $this->error('Insufficient stock.', 422);
        }

        $cartItem->update(['quantity' => $data['quantity']]);

        return $this->success($this->buildCartResponse($request), 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem): JsonResponse
    {
        abort_if($cartItem->user_id !== $request->user()->id, 403);
        $cartItem->delete();

        return $this->success($this->buildCartResponse($request), 'Item removed.');
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:30']]);

        $cart = $this->buildCartResponse($request, $data['code']);

        if (! $cart['coupon_applied']) {
            return $this->error('Invalid or inapplicable coupon code.', 422);
        }

        return $this->success($cart, 'Coupon applied.');
    }

    private function buildCartResponse(Request $request, ?string $couponCode = null): array
    {
        $items = $request->user()->cartItems()
            ->with([
                'product' => fn ($q) => $q->with(['vendor', 'images'])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->with(['reviews' => fn ($r) => $r->with('user')->latest()->limit(3)]),
            ])
            ->get();

        $subtotal = $items->sum(fn ($item) => (float) ($item->product->sale_price ?? $item->product->price) * $item->quantity);
        $discount = 0;
        $couponApplied = false;
        $couponMessage = null;

        if ($couponCode) {
            $coupon = app(CouponService::class)->find($couponCode, CouponAppliesTo::Order);
            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
                $couponApplied = true;
                $couponMessage = $coupon->code;
            }
        }

        $pricing = app(TaxService::class)->calculate($subtotal, $discount);
        $firstItem = $items->first();

        return [
            'vendor' => $firstItem ? UserApiFormatter::vendor($firstItem->product->vendor) : null,
            'items' => $items->map(fn ($i) => UserApiFormatter::cartItem($i)),
            'summary' => $pricing,
            'coupon_applied' => $couponApplied,
            'coupon_code' => $couponMessage,
            'items_count' => $items->sum('quantity'),
        ];
    }
}
