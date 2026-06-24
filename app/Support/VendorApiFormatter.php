<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWithdrawal;

class VendorApiFormatter
{
    public static function user(User $user): array
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'role' => $user->role?->value,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'profile_image' => ($user->avatar ? asset('storage/'.$user->avatar) : null)
                ?? ($user->relationLoaded('vendor') && $user->vendor?->shop_logo
                    ? asset('storage/'.$user->vendor->shop_logo)
                    : null),
            'address' => $user->address,
            'wallet_balance' => round((float) $user->wallet_balance, 2),
        ];

        if ($user->relationLoaded('vendor') && $user->vendor) {
            $data['vendor'] = self::vendor($user->vendor);
        }

        return $data;
    }

    public static function vendor(Vendor $vendor): array
    {
        $documents = $vendor->relationLoaded('documents')
            ? $vendor->documents->mapWithKeys(function ($doc) {
                return [$doc->document_type => asset('storage/'.$doc->file_path)];
            })->all()
            : [];

        return [
            'id' => $vendor->id,
            'shop_name' => $vendor->shop_name,
            'owner_name' => $vendor->owner_name,
            'mobile' => $vendor->mobile,
            'business_mobile' => $vendor->business_mobile,
            'email' => $vendor->email,
            'address' => $vendor->address,
            'country' => $vendor->country,
            'state' => $vendor->state,
            'city' => $vendor->city,
            'pincode' => $vendor->pincode,
            'gst_number' => $vendor->gst_number,
            'shop_logo' => $vendor->shop_logo ? asset('storage/'.$vendor->shop_logo) : null,
            'status' => $vendor->status->value,
            'rejection_reason' => $vendor->rejection_reason,
            'approved_at' => $vendor->approved_at?->toIso8601String(),
            'bank' => [
                'account_holder_name' => $vendor->account_holder_name,
                'account_number' => $vendor->account_number,
                'ifsc_code' => $vendor->ifsc_code,
                'bank_name' => $vendor->bank_name,
                'account_type' => $vendor->account_type,
                'masked_account_number' => self::maskAccountNumber($vendor->account_number),
            ],
            'documents' => $documents,
        ];
    }

    public static function ownerDetails(User $user): array
    {
        $vendor = $user->vendor;

        return [
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'aadhar_card' => $vendor?->documents->firstWhere('document_type', 'Aadhar Card')
                ? asset('storage/'.$vendor->documents->firstWhere('document_type', 'Aadhar Card')->file_path)
                : null,
        ];
    }

    public static function shopDetails(Vendor $vendor): array
    {
        $documents = $vendor->relationLoaded('documents') ? $vendor->documents : collect();

        return [
            'shop_name' => $vendor->shop_name,
            'business_mobile' => $vendor->business_mobile,
            'email' => $vendor->email,
            'gst_number' => $vendor->gst_number,
            'address' => $vendor->address,
            'country' => $vendor->country,
            'state' => $vendor->state,
            'city' => $vendor->city,
            'pincode' => $vendor->pincode,
            'shop_logo' => $vendor->shop_logo ? asset('storage/'.$vendor->shop_logo) : null,
            'pan_card' => optional($documents->firstWhere('document_type', 'PAN Card'))
                ? asset('storage/'.$documents->firstWhere('document_type', 'PAN Card')->file_path)
                : null,
        ];
    }

    public static function product(Product $product, bool $detailed = false): array
    {
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = (float) $product->price;
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $rating = round((float) ($product->reviews_avg_rating ?? 0), 1);
        $reviewsCount = (int) ($product->reviews_count ?? 0);
        $discountPercent = $originalPrice > 0 && $product->sale_price
            ? (int) round((($originalPrice - $price) / $originalPrice) * 100)
            : 0;

        $data = [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => $price,
            'original_price' => $originalPrice,
            'discount_percent' => $discountPercent,
            'stock' => $product->stock,
            'stock_label' => $product->stock.' In Stock',
            'status' => (bool) $product->status,
            'category' => $product->category?->name,
            'category_id' => $product->category_id,
            'subcategory' => $product->subcategory?->name,
            'subcategory_id' => $product->subcategory_id,
            'brand' => $product->vendor?->shop_name,
            'image' => $primaryImage ? asset('storage/'.$primaryImage->image_path) : null,
            'images' => $product->relationLoaded('images')
                ? $product->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/'.$img->image_path),
                    'is_primary' => (bool) $img->is_primary,
                ])->values()
                : [],
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
        ];

        if ($detailed && $product->relationLoaded('reviews')) {
            $data['reviews'] = $product->reviews->map(fn (ProductReview $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user_name' => $review->relationLoaded('user') ? $review->user->name : null,
                'created_at' => $review->created_at->diffForHumans(),
            ])->values();
        }

        return $data;
    }

    public static function orderStatus(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'new',
            OrderStatus::Accepted, OrderStatus::Packed => 'accepted',
            OrderStatus::Shipped => 'out_for_delivery',
            OrderStatus::Delivered => 'delivered',
            OrderStatus::Cancelled => 'cancelled',
            default => $status->value,
        };
    }

    public static function orderStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending => 'NEW',
            OrderStatus::Accepted => 'ACCEPTED',
            OrderStatus::Packed => 'ACCEPTED',
            OrderStatus::Shipped => 'OUT FOR DELIVERY',
            OrderStatus::Delivered => 'DELIVERED',
            OrderStatus::Cancelled => 'CANCELLED',
            default => strtoupper($status->value),
        };
    }

    public static function order(Order $order, bool $detailed = false): array
    {
        $primaryItem = $order->items->first();
        $customer = self::orderCustomer($order);

        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => self::orderStatus($order->status),
            'status_label' => self::orderStatusLabel($order->status),
            'status_raw' => $order->status->value,
            'total_amount' => (float) $order->total_amount,
            'customer_name' => $customer['name'] ?? null,
            'customer_mobile' => $customer['mobile'] ?? null,
            'customer_image' => $customer['image'] ?? null,
            'customer' => $customer,
            'address_type' => $order->shipping_address_label,
            'shipping_address' => $order->shipping_address,
            'created_at' => $order->created_at->format('M d, Y'),
            'created_at_full' => $order->created_at->format('M d, Y | g:i A'),
            'can_accept' => $order->status === OrderStatus::Pending,
            'can_reject' => $order->status === OrderStatus::Pending,
            'can_mark_out_for_delivery' => in_array($order->status, [OrderStatus::Accepted, OrderStatus::Packed], true),
            'can_deliver' => $order->status === OrderStatus::Shipped,
            'product_name' => $primaryItem?->product_name,
            'quantity' => $primaryItem?->quantity,
            'price' => $primaryItem ? (float) $primaryItem->total_price : (float) $order->total_amount,
            'image' => $primaryItem && $primaryItem->relationLoaded('product') && $primaryItem->product
                ? optional($primaryItem->product->images->first())->image_path
                    ? asset('storage/'.$primaryItem->product->images->first()->image_path)
                    : null
                : null,
        ];

        if ($detailed || $order->relationLoaded('items')) {
            $data['items'] = $order->items->map(function ($item) {
                $image = $item->relationLoaded('product') && $item->product
                    ? optional($item->product->images->first())->image_path
                    : null;

                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'price' => (float) $item->total_price,
                    'image' => $image ? asset('storage/'.$image) : null,
                ];
            })->values();
            $data['subtotal'] = (float) $order->subtotal;
            $data['tax'] = (float) $order->tax_amount;
            $data['shipping'] = (float) $order->shipping_amount;
            $data['discount'] = (float) $order->discount_amount;
            $data['payment'] = $order->relationLoaded('payment') && $order->payment ? [
                'payment_id' => $order->payment->payment_id,
                'method' => self::paymentMethodLabel($order->payment->method),
                'status' => strtoupper($order->payment->status->value),
                'amount' => (float) $order->payment->amount,
            ] : null;
        }

        return $data;
    }

    private static function orderCustomer(Order $order): ?array
    {
        if (! $order->relationLoaded('user') || ! $order->user) {
            return null;
        }

        $user = $order->user;
        $image = $user->avatar ? asset('storage/'.$user->avatar) : null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'image' => $image,
            'avatar' => $image,
        ];
    }

    public static function earningsSummary(Vendor $vendor, float $totalEarnings, float $walletAmount): array
    {
        return [
            'total_earnings' => round($totalEarnings, 2),
            'wallet_amount' => round($walletAmount, 2),
            'bank' => [
                'bank_name' => $vendor->bank_name,
                'masked_account_number' => self::maskAccountNumber($vendor->account_number),
            ],
        ];
    }

    public static function transaction(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'transaction_id' => $payment->payment_id,
            'status' => strtoupper($payment->status === PaymentStatus::Completed ? 'paid' : $payment->status->value),
            'amount' => (float) $payment->amount,
            'formatted_amount' => '+₹'.number_format((float) $payment->amount, 0),
            'method' => self::paymentMethodLabel($payment->method),
            'customer_name' => $payment->relationLoaded('user') ? $payment->user->name : null,
            'created_at' => $payment->created_at->format('M d, Y | g:i A'),
        ];
    }

    public static function withdrawal(VendorWithdrawal $withdrawal): array
    {
        return [
            'id' => $withdrawal->id,
            'transaction_id' => $withdrawal->transaction_id,
            'status' => strtoupper($withdrawal->status->value),
            'amount' => (float) $withdrawal->amount,
            'formatted_amount' => '-₹'.number_format((float) $withdrawal->amount, 0),
            'method' => 'Bank Transfer',
            'bank_name' => $withdrawal->bank_name,
            'created_at' => $withdrawal->created_at->format('M d, Y | g:i A'),
        ];
    }

    public static function paymentMethodLabel(PaymentMethod $method): string
    {
        return $method === PaymentMethod::Cod ? 'Cash' : 'Online';
    }

    private static function maskAccountNumber(?string $accountNumber): ?string
    {
        if (! $accountNumber) {
            return null;
        }

        $lastFour = substr($accountNumber, -4);

        return '**** '.$lastFour;
    }
}
