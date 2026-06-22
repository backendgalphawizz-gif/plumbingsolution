<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Enums\CouponAppliesTo;
use App\Models\CartItem;
use App\Models\Product;
use App\Services\CouponService;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $existingVendorId = $request->user()->cartItems()
            ->with('product')
            ->first()?->product?->vendor_id;

        if ($existingVendorId && $existingVendorId !== $product->vendor_id) {
            return $this->error('Cart can only contain products from a single vendor. Please remove existing items first.', 422);
        }

        CartItem::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'product_id' => $product->id,
                'product_variant_id' => $data['product_variant_id'] ?? null,
            ],
            ['quantity' => $data['quantity']]
        );

        return $this->success($this->buildCartResponse($request), 'Item added to cart.', 201);
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
        $shipping = $subtotal > 0 ? 5.00 : 0;
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

        $taxable = max(0, $subtotal - $discount);
        $tax = round($taxable * 0.08, 2);
        $total = round($taxable + $shipping + $tax, 2);
        $firstItem = $items->first();

        return [
            'vendor' => $firstItem ? UserApiFormatter::vendor($firstItem->product->vendor) : null,
            'items' => $items->map(fn ($i) => UserApiFormatter::cartItem($i)),
            'summary' => [
                'subtotal' => round($subtotal, 2),
                'shipping' => $shipping,
                'tax' => $tax,
                'tax_percent' => 8,
                'discount' => $discount,
                'total' => $total,
            ],
            'coupon_applied' => $couponApplied,
            'coupon_code' => $couponMessage,
            'items_count' => $items->sum('quantity'),
        ];
    }
}
