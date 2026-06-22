<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\CouponAppliesTo;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusLog;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\UserAddress;
use App\Services\CouponService;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    use ApiResponse;

    public function paymentMethods(): JsonResponse
    {
        return $this->success([
            ['code' => 'razorpay', 'name' => 'Razorpay', 'icon' => 'razorpay'],
            ['code' => 'cod', 'name' => 'Cash on Delivery', 'icon' => 'cod'],
        ]);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_id' => ['required', 'exists:user_addresses,id'],
            'payment_method' => ['required', 'in:razorpay,cod'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'coupon_code' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['payment_method'] === 'razorpay' && empty($data['transaction_id'])) {
            return $this->error('Transaction ID is required for online payment.', 422);
        }

        $user = $request->user();
        $address = UserAddress::where('user_id', $user->id)->findOrFail($data['address_id']);

        $cartItems = $user->cartItems()->with(['product.vendor', 'product.images'])->get();

        if ($cartItems->isEmpty()) {
            return $this->error('Your cart is empty.', 422);
        }

        $vendorIds = $cartItems->pluck('product.vendor_id')->unique()->filter();
        if ($vendorIds->count() > 1) {
            return $this->error('Cart can only contain products from a single vendor.', 422);
        }

        foreach ($cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                return $this->error("Insufficient stock for {$item->product->product_name}.", 422);
            }
        }

        $subtotal = $cartItems->sum(fn ($item) => (float) ($item->product->sale_price ?? $item->product->price) * $item->quantity);
        $shipping = 5.00;
        $discount = 0;

        if (! empty($data['coupon_code'])) {
            $coupon = app(CouponService::class)->find($data['coupon_code'], CouponAppliesTo::Order);
            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->calculateDiscount($subtotal);
            }
        }

        $taxable = max(0, $subtotal - $discount);
        $tax = round($taxable * 0.08, 2);
        $total = round($taxable + $shipping + $tax, 2);

        $order = Order::create([
            'order_number' => 'ORD-'.strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'vendor_id' => $cartItems->first()->product->vendor_id,
            'status' => OrderStatus::Pending,
            'subtotal' => $subtotal,
            'tax_amount' => $tax,
            'shipping_amount' => $shipping,
            'discount_amount' => $discount,
            'total_amount' => $total,
            'shipping_address' => $address->full_address ?? implode(', ', array_filter([
                $address->full_name, $address->house_no, $address->road_area,
                $address->city, $address->state, $address->pincode,
            ])),
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($cartItems as $item) {
            $price = (float) ($item->product->sale_price ?? $item->product->price);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->product_name,
                'sku' => $item->product->sku,
                'quantity' => $item->quantity,
                'unit_price' => $price,
                'total_price' => $price * $item->quantity,
            ]);

            $item->product->decrement('stock', $item->quantity);
        }

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => OrderStatus::Pending->value,
            'notes' => 'Order placed by customer',
        ]);

        $paymentStatus = $data['payment_method'] === 'cod' ? PaymentStatus::Pending : PaymentStatus::Completed;

        $payment = Payment::create([
            'payment_id' => 'PAY-'.strtoupper(Str::random(10)),
            'user_id' => $user->id,
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'method' => PaymentMethod::from($data['payment_method']),
            'status' => $paymentStatus,
            'amount' => $total,
            'currency' => 'INR',
            'gateway_payment_id' => $data['transaction_id'] ?? null,
        ]);

        if (! empty($data['transaction_id'])) {
            Transaction::create([
                'payment_id' => $payment->id,
                'transaction_id' => $data['transaction_id'],
                'type' => 'payment',
                'amount' => $total,
                'status' => 'completed',
                'description' => 'Order payment',
            ]);
        }

        $user->cartItems()->delete();

        $order->load(['items', 'vendor', 'payment']);

        $response = [
            'order' => UserApiFormatter::order($order, detailed: true),
            'payment' => [
                'payment_id' => $payment->payment_id,
                'method' => $payment->method->value,
                'status' => $payment->status->value,
                'amount' => (float) $payment->amount,
                'transaction_id' => $payment->gateway_payment_id,
            ],
        ];

        if ($data['payment_method'] === 'razorpay' && empty($data['transaction_id'])) {
            $response['razorpay'] = [
                'order_id' => 'order_'.Str::random(14),
                'amount' => (int) ($total * 100),
                'currency' => 'INR',
                'key' => config('services.razorpay.key', 'rzp_test_placeholder'),
            ];
        }

        return $this->success($response, 'Order placed successfully.', 201);
    }
}
