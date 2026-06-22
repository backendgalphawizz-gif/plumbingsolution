<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Models\Banner;
use App\Models\BulkOrder;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use App\Models\User;
use App\Models\Vendor;
use App\Services\QuotationService;

class UserApiFormatter
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
            'profile_image' => $user->avatar ? asset('storage/'.$user->avatar) : null,
            'address' => $user->address,
        ];

        if ($user->relationLoaded('serviceProvider') && $user->serviceProvider) {
            $data['provider'] = self::providerRegistration($user->serviceProvider);
        }

        return $data;
    }

    public static function providerRegistration(ServiceProvider $provider): array
    {
        $documents = $provider->relationLoaded('documents')
            ? $provider->documents->mapWithKeys(function ($doc) {
                return [$doc->document_type => asset('storage/'.$doc->file_path)];
            })->all()
            : [];

        return [
            'id' => $provider->id,
            'status' => $provider->status->value,
            'skills' => $provider->skills ?? [],
            'experience' => $provider->experience_years,
            'service_area' => $provider->service_area,
            'bank' => [
                'account_number' => $provider->account_number,
                'account_holder_name' => $provider->account_holder_name,
                'ifsc_code' => $provider->ifsc_code,
                'bank_name' => $provider->bank_name,
                'account_type' => $provider->account_type,
            ],
            'documents' => $documents,
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

    public static function product(Product $product, bool $detailed = false): array
    {
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = (float) $product->price;
        $discount = self::discountMeta($originalPrice, $product->sale_price);
        $primaryImage = $product->images->firstWhere('is_primary', true) ?? $product->images->first();
        $rating = round((float) ($product->reviews_avg_rating ?? $product->rating ?? 0), 1);
        $reviewsCount = (int) ($product->reviews_count ?? 0);

        $data = [
            'id' => $product->id,
            'name' => $product->product_name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => $price,
            'original_price' => $originalPrice,
            'discount' => $discount,
            'stock' => $product->stock,
            'category' => $product->category?->name,
            'subcategory' => $product->subcategory?->name,
            'vendor' => $product->vendor ? self::vendor($product->vendor) : null,
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
            'reviews' => $detailed && $product->relationLoaded('reviews')
                ? $product->reviews->map(fn ($r) => self::review($r))->values()
                : [],
        ];

        return $data;
    }

    public static function review(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'user_name' => $review->relationLoaded('user') ? $review->user->name : null,
            'created_at' => $review->created_at->format('M d, Y'),
        ];
    }

    public static function vendor(?Vendor $vendor): ?array
    {
        if (! $vendor) {
            return null;
        }

        return [
            'id' => $vendor->id,
            'shop_name' => $vendor->shop_name,
            'owner_name' => $vendor->owner_name,
            'mobile' => $vendor->mobile,
            'address' => $vendor->address,
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
            'category_id' => $service->service_category_id,
            'image' => $service->image ? asset('storage/'.$service->image) : null,
        ];
    }

    public static function serviceProvider(ServiceProvider $provider, bool $detailed = false): array
    {
        $primaryImage = $provider->relationLoaded('images')
            ? ($provider->images->firstWhere('is_primary', true) ?? $provider->images->first())
            : null;

        $data = [
            'id' => $provider->id,
            'name' => $provider->name,
            'mobile' => $provider->mobile,
            'skills' => $provider->skills ?? [],
            'experience_years' => $provider->experience_years,
            'service_area' => $provider->service_area,
            'image' => $provider->avatar
                ? asset('storage/'.$provider->avatar)
                : ($primaryImage ? asset('storage/'.$primaryImage->image_path) : null),
            'images' => self::serviceProviderImages($provider),
            'rating' => round((float) ($provider->reviews_avg_rating ?? 0), 1),
            'reviews_count' => (int) ($provider->reviews_count ?? 0),
            'services_count' => $provider->relationLoaded('services') ? $provider->services->count() : null,
        ];

        if ($detailed) {
            $data['services'] = $provider->relationLoaded('services')
                ? $provider->services->map(fn ($s) => self::service($s))->values()
                : [];
            $data['reviews'] = $provider->relationLoaded('reviews')
                ? $provider->reviews->map(fn ($r) => self::serviceProviderReview($r))->values()
                : [];
        }

        return $data;
    }

    public static function serviceProviderReview(ServiceProviderReview $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'user_name' => $review->relationLoaded('user') ? $review->user->name : null,
            'created_at' => $review->created_at->format('M d, Y'),
        ];
    }

    public static function cartItem(CartItem $item): array
    {
        $product = $item->product;
        $price = (float) ($product->sale_price ?? $product->price);
        $originalPrice = (float) $product->price;

        return [
            'id' => $item->id,
            'product_id' => $product->id,
            'name' => $product->product_name,
            'vendor_id' => $product->vendor_id,
            'vendor' => self::vendor($product->vendor),
            'price' => $price,
            'original_price' => $originalPrice,
            'discount' => self::discountMeta($originalPrice, $product->sale_price),
            'quantity' => $item->quantity,
            'line_total' => round($price * $item->quantity, 2),
            'image' => optional($product->images->first())->image_path
                ? asset('storage/'.$product->images->first()->image_path)
                : null,
            'images' => $product->images->map(fn ($img) => asset('storage/'.$img->image_path))->values(),
            'rating' => round((float) ($product->reviews_avg_rating ?? 0), 1),
            'reviews_count' => (int) ($product->reviews_count ?? 0),
            'reviews' => $product->relationLoaded('reviews')
                ? $product->reviews->take(3)->map(fn ($r) => self::review($r))->values()
                : [],
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
            'vendor' => self::vendor($order->vendor),
            'payment_status' => $order->relationLoaded('payment') && $order->payment
                ? $order->payment->status->value
                : null,
            'invoice_url' => url('/api/user/orders/'.$order->id.'/invoice'),
        ];

        if ($detailed || $order->relationLoaded('items')) {
            $data['items'] = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'sku' => $item->sku,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'price' => (float) $item->total_price,
            ])->values();
            $data['subtotal'] = (float) $order->subtotal;
            $data['shipping'] = (float) $order->shipping_amount;
            $data['tax'] = (float) $order->tax_amount;
            $data['discount'] = (float) $order->discount_amount;
            $data['shipping_address'] = $order->shipping_address;
            $data['tracking'] = [
                'tracking_number' => $order->tracking_number,
                'courier_name' => $order->courier_name,
            ];
            $data['payment'] = $order->relationLoaded('payment') && $order->payment ? [
                'payment_id' => $order->payment->payment_id,
                'method' => $order->payment->method->value,
                'status' => $order->payment->status->value,
                'amount' => (float) $order->payment->amount,
                'transaction_id' => $order->payment->gateway_payment_id,
            ] : null;
            $data['tracking_timeline'] = $order->relationLoaded('statusLogs')
                ? $order->statusLogs->map(fn ($log) => [
                    'status' => $log->status,
                    'notes' => $log->notes,
                    'created_at' => $log->created_at->format('M d, Y • g:i A'),
                ])->values()
                : [];
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
        $isRescheduled = $booking->rescheduled_at
            && ! in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Completed]);

        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'service_id' => $booking->service_id,
            'service_name' => $booking->service_name,
            'status' => $isRescheduled ? 'reschedule' : self::bookingStatusLabel($booking->status),
            'status_raw' => $booking->status->value,
            'is_rescheduled' => (bool) $booking->rescheduled_at,
            'rescheduled_at' => $booking->rescheduled_at?->format('M d, Y • g:i A'),
            'cancellation_reason' => $booking->cancellation_reason,
            'subtotal' => (float) ($booking->subtotal ?? $booking->amount),
            'discount' => (float) ($booking->discount_amount ?? 0),
            'promo_code' => $booking->coupon_code,
            'amount' => (float) $booking->amount,
            'address' => $booking->address,
            'schedule_date' => $booking->scheduled_at?->format('Y-m-d'),
            'schedule_time' => $booking->scheduled_at?->format('H:i'),
            'scheduled_at' => $booking->scheduled_at?->format('M d, Y • g:i A'),
            'notes' => $booking->notes,
            'issue_images' => $booking->relationLoaded('images')
                ? $booking->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => asset('storage/'.$image->image_path),
                    'caption' => $image->caption,
                ])->values()->all()
                : [],
            'service_provider' => $booking->serviceProvider ? [
                'id' => $booking->serviceProvider->id,
                'name' => $booking->serviceProvider->name,
            ] : null,
            'provider' => $booking->serviceProvider?->name,
            'can_cancel' => ! in_array($booking->status, [BookingStatus::Completed, BookingStatus::Cancelled]),
            'can_reschedule' => in_array($booking->status, [BookingStatus::Pending, BookingStatus::Assigned, BookingStatus::Accepted]),
            'can_review' => $booking->status === BookingStatus::Completed
                && ($booking->relationLoaded('review') ? $booking->review === null : true),
            'has_review' => $booking->relationLoaded('review') && $booking->review !== null,
            'rating' => $booking->relationLoaded('review') && $booking->review
                ? $booking->review->rating
                : null,
            'review' => $booking->relationLoaded('review') && $booking->review
                ? self::serviceProviderReview($booking->review)
                : null,
            'created_at' => $booking->created_at->format('M d, Y • g:i A'),
            'payment' => $booking->relationLoaded('payment') && $booking->payment ? [
                'payment_id' => $booking->payment->payment_id,
                'method' => $booking->payment->method->value,
                'status' => $booking->payment->status->value,
                'amount' => (float) $booking->payment->amount,
                'transaction_id' => $booking->payment->gateway_payment_id,
            ] : null,
        ];
    }

    public static function bulkOrderStatusLabel(string $status): string
    {
        return match ($status) {
            'requirement_submitted', 'admin_review', 'quotation_generated' => 'submitted',
            'quotation_sent' => 'quotation',
            'customer_approved', 'order_created' => 'approved',
            'customer_rejected' => 'rejected',
            default => $status,
        };
    }

    public static function bulkOrder(BulkOrder $bulkOrder, bool $detailed = false): array
    {
        $activeQuotation = $bulkOrder->relationLoaded('quotations')
            ? $bulkOrder->quotations->sortByDesc('id')->first()
            : null;

        $data = [
            'type' => 'bulk_order',
            'id' => $bulkOrder->id,
            'reference_number' => $bulkOrder->reference_number,
            'full_name' => $bulkOrder->full_name,
            'mobile' => $bulkOrder->mobile,
            'note' => $bulkOrder->requirement_description,
            'status' => self::bulkOrderStatusLabel($bulkOrder->status),
            'status_raw' => $bulkOrder->status,
            'can_accept_quotation' => $bulkOrder->status === 'quotation_sent'
                && $activeQuotation?->status === 'sent',
            'can_reject_quotation' => $bulkOrder->status === 'quotation_sent'
                && $activeQuotation?->status === 'sent',
            'created_at' => $bulkOrder->created_at->format('M d, Y'),
            'created_at_full' => $bulkOrder->created_at->format('M d, Y • g:i A'),
        ];

        if ($detailed || $bulkOrder->relationLoaded('files')) {
            $data['files'] = $bulkOrder->relationLoaded('files')
                ? $bulkOrder->files->map(fn ($file) => [
                    'id' => $file->id,
                    'url' => asset('storage/'.$file->file_path),
                    'file_type' => $file->file_type,
                    'original_name' => $file->original_name,
                ])->values()->all()
                : [];
        }

        if ($detailed || $bulkOrder->relationLoaded('quotations')) {
            $formatter = app(QuotationService::class);
            $data['quotations'] = $bulkOrder->relationLoaded('quotations')
                ? $bulkOrder->quotations->map(fn ($q) => $formatter->format($q))->values()->all()
                : [];
            $data['quotation'] = $activeQuotation ? $formatter->format($activeQuotation) : null;
        }

        if ($bulkOrder->relationLoaded('payment') && $bulkOrder->payment) {
            $data['payment'] = [
                'payment_id' => $bulkOrder->payment->payment_id,
                'method' => $bulkOrder->payment->method->value,
                'status' => $bulkOrder->payment->status->value,
                'amount' => (float) $bulkOrder->payment->amount,
                'transaction_id' => $bulkOrder->payment->gateway_payment_id,
            ];
        }

        return $data;
    }

    private static function discountMeta(float $originalPrice, mixed $salePrice): array
    {
        $sale = $salePrice !== null ? (float) $salePrice : null;
        $hasDiscount = $sale !== null && $sale < $originalPrice;
        $discountAmount = $hasDiscount ? round($originalPrice - $sale, 2) : 0;
        $discountPercent = $hasDiscount && $originalPrice > 0
            ? round(($discountAmount / $originalPrice) * 100)
            : 0;

        return [
            'has_discount' => $hasDiscount,
            'amount' => $discountAmount,
            'percent' => $discountPercent,
        ];
    }

    private static function serviceProviderImages(ServiceProvider $provider): array
    {
        if (! $provider->relationLoaded('images')) {
            return $provider->avatar
                ? [['id' => null, 'url' => asset('storage/'.$provider->avatar), 'is_primary' => true]]
                : [];
        }

        $images = collect();

        if ($provider->avatar) {
            $images->push([
                'id' => null,
                'url' => asset('storage/'.$provider->avatar),
                'is_primary' => true,
            ]);
        }

        foreach ($provider->images as $image) {
            $url = asset('storage/'.$image->image_path);
            if ($images->contains('url', $url)) {
                continue;
            }

            $images->push([
                'id' => $image->id,
                'url' => $url,
                'is_primary' => (bool) $image->is_primary,
            ]);
        }

        return $images->values()->all();
    }
}

