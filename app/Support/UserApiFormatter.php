<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\User;

class UserApiFormatter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'address' => $user->address,
        ];
    }

    public static function banner(Banner $banner): array
    {
        return [
            'id' => $banner->id,
            'title' => $banner->title,
            'image' => asset('storage/'.$banner->image),
            'redirect_type' => $banner->redirect_type,
            'redirect_id' => $banner->redirect_id,
            'redirect_url' => $banner->redirect_url,
        ];
    }

    public static function product(Product $product): array
    {
        $price = $product->sale_price ?? $product->price;
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();

        return [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => (float) $price,
            'original_price' => (float) $product->price,
            'stock' => $product->stock,
            'category' => $product->category?->name,
            'vendor' => $product->vendor?->shop_name,
            'image' => $primaryImage ? asset('storage/'.$primaryImage->image_path) : null,
            'rating' => 4.8,
        ];
    }

    public static function service(Service $service): array
    {
        return [
            'id' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'description' => $service->description,
            'starting_price' => (float) $service->starting_price,
            'rating' => (float) $service->rating,
            'providers_count' => $service->providers_count,
            'category' => $service->category?->name,
            'image' => $service->image ? asset('storage/'.$service->image) : null,
        ];
    }

    public static function cartItem(CartItem $item): array
    {
        $product = $item->product;
        $price = $product->sale_price ?? $product->price;

        return [
            'id' => $item->id,
            'product_id' => $product->id,
            'name' => $product->product_name,
            'vendor' => $product->vendor?->shop_name,
            'price' => (float) $price,
            'quantity' => $item->quantity,
            'line_total' => round((float) $price * $item->quantity, 2),
            'image' => optional($product->images->first())->image_path
                ? asset('storage/'.$product->images->first()->image_path)
                : null,
        ];
    }

    public static function orderStatusLabel(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed => 'processing',
            OrderStatus::Shipped => 'out_for_delivery',
            OrderStatus::Delivered => 'delivered',
            OrderStatus::Cancelled => 'cancelled',
            default => $status->value,
        };
    }

    public static function order(Order $order, bool $detailed = false): array
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => self::orderStatusLabel($order->status),
            'status_raw' => $order->status->value,
            'total_amount' => (float) $order->total_amount,
            'items_count' => $order->items->count(),
            'can_cancel' => in_array($order->status, [OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed]),
            'created_at' => $order->created_at->format('M d, Y'),
            'created_at_full' => $order->created_at->format('M d, Y • g:i A'),
        ];

        if ($detailed || $order->relationLoaded('items')) {
            $data['items'] = $order->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'vendor' => $order->vendor?->shop_name,
                'quantity' => $item->quantity,
                'price' => (float) $item->total_price,
            ])->values();
            $data['subtotal'] = (float) $order->subtotal;
            $data['shipping'] = (float) $order->shipping_amount;
            $data['tax'] = (float) $order->tax_amount;
            $data['discount'] = (float) $order->discount_amount;
            $data['shipping_address'] = $order->shipping_address;
        }

        return $data;
    }

    public static function bookingStatusLabel(BookingStatus $status): string
    {
        return match ($status) {
            BookingStatus::Pending, BookingStatus::Assigned => 'processing',
            BookingStatus::Accepted, BookingStatus::Started => 'confirmed',
            BookingStatus::Completed => 'completed',
            BookingStatus::Cancelled => 'cancelled',
            default => $status->value,
        };
    }

    public static function booking(ServiceBooking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'service_name' => $booking->service_name,
            'status' => self::bookingStatusLabel($booking->status),
            'status_raw' => $booking->status->value,
            'amount' => (float) $booking->amount,
            'address' => $booking->address,
            'scheduled_at' => $booking->scheduled_at?->format('M d, Y • g:i A'),
            'provider' => $booking->serviceProvider?->name,
            'can_cancel' => ! in_array($booking->status, [BookingStatus::Completed, BookingStatus::Cancelled]),
            'can_reschedule' => in_array($booking->status, [BookingStatus::Pending, BookingStatus::Assigned, BookingStatus::Accepted]),
            'created_at' => $booking->created_at->format('M d, Y • g:i A'),
        ];
    }
}
