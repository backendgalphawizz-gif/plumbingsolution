<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\ProviderWithdrawal;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use App\Models\User;

class ProviderApiFormatter
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
            'address' => $user->address,
        ];

        if ($user->relationLoaded('serviceProvider') && $user->serviceProvider) {
            $data['provider'] = self::provider($user->serviceProvider);
        }

        return $data;
    }

    public static function provider(ServiceProvider $provider): array
    {
        $documents = $provider->relationLoaded('documents')
            ? $provider->documents->mapWithKeys(fn ($doc) => [
                $doc->document_type => asset('storage/'.$doc->file_path),
            ])->all()
            : [];

        return [
            'id' => $provider->id,
            'name' => $provider->name,
            'mobile' => $provider->mobile,
            'avatar' => $provider->avatar ? asset('storage/'.$provider->avatar) : null,
            'skills' => $provider->skills ?? [],
            'experience' => $provider->experience_years,
            'service_area' => $provider->service_area,
            'status' => $provider->status->value,
            'rejection_reason' => $provider->rejection_reason,
            'approved_at' => $provider->approved_at?->toIso8601String(),
            'bank' => [
                'account_holder_name' => $provider->account_holder_name,
                'account_number' => $provider->account_number,
                'ifsc_code' => $provider->ifsc_code,
                'bank_name' => $provider->bank_name,
                'account_type' => $provider->account_type,
                'masked_account_number' => self::maskAccountNumber($provider->account_number),
            ],
            'documents' => $documents,
        ];
    }

    public static function personalDetails(User $user): array
    {
        $provider = $user->serviceProvider;

        return [
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'address' => $user->address,
            'service_area' => $provider?->service_area,
            'avatar' => $user->avatar ? asset('storage/'.$user->avatar) : null,
        ];
    }

    public static function bankDetails(ServiceProvider $provider): array
    {
        return [
            'account_holder_name' => $provider->account_holder_name,
            'account_number' => $provider->account_number,
            'ifsc_code' => $provider->ifsc_code,
            'bank_name' => $provider->bank_name,
            'account_type' => $provider->account_type,
            'masked_account_number' => self::maskAccountNumber($provider->account_number),
        ];
    }

    public static function skillsDetails(ServiceProvider $provider): array
    {
        return [
            'skills' => $provider->skills ?? [],
            'experience' => $provider->experience_years,
        ];
    }

    public static function bookingStatus(BookingStatus $status, bool $isRescheduled = false): string
    {
        if ($isRescheduled) {
            return 'rescheduled';
        }

        return match ($status) {
            BookingStatus::Pending, BookingStatus::Assigned => 'new',
            BookingStatus::Accepted => 'accepted',
            BookingStatus::Started => 'ongoing',
            BookingStatus::Completed => 'completed',
            BookingStatus::Cancelled => 'cancelled',
            default => $status->value,
        };
    }

    public static function bookingStatusLabel(BookingStatus $status, bool $isRescheduled = false): string
    {
        if ($isRescheduled) {
            return 'RESCHEDULED';
        }

        return match ($status) {
            BookingStatus::Pending, BookingStatus::Assigned => 'NEW',
            BookingStatus::Accepted => 'ACCEPTED',
            BookingStatus::Started => 'ONGOING',
            BookingStatus::Completed => 'COMPLETED',
            BookingStatus::Cancelled => 'CANCELLED',
            default => strtoupper($status->value),
        };
    }

    public static function booking(ServiceBooking $booking, bool $detailed = false): array
    {
        $isRescheduled = (bool) ($booking->rescheduled_at
            && ! in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Completed]));

        $data = [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'service_id' => $booking->service_id,
            'service_name' => $booking->service_name,
            'status' => self::bookingStatus($booking->status, $isRescheduled),
            'status_label' => self::bookingStatusLabel($booking->status, $isRescheduled),
            'status_raw' => $booking->status->value,
            'amount' => (float) $booking->amount,
            'customer_name' => $booking->relationLoaded('user') ? $booking->user->name : null,
            'customer_mobile' => $booking->relationLoaded('user') ? $booking->user->mobile : null,
            'address' => $booking->address,
            'scheduled_at' => $booking->scheduled_at?->format('M d, Y • g:i A'),
            'created_at' => $booking->created_at->format('M d, Y • g:i A'),
            'can_accept' => $booking->status === BookingStatus::Assigned,
            'can_reject' => $booking->status === BookingStatus::Assigned,
            'can_start' => $booking->status === BookingStatus::Accepted,
            'can_complete' => $booking->status === BookingStatus::Started,
        ];

        if ($detailed) {
            $data['notes'] = $booking->notes;
            $data['description'] = $booking->description;
            $data['subtotal'] = (float) ($booking->subtotal ?? $booking->amount);
            $data['discount'] = (float) ($booking->discount_amount ?? 0);
            $data['promo_code'] = $booking->coupon_code;
            $data['issue_images'] = $booking->relationLoaded('images')
                ? $booking->images->map(fn ($image) => [
                    'id' => $image->id,
                    'url' => asset('storage/'.$image->image_path),
                ])->values()
                : [];
            $data['customer'] = $booking->relationLoaded('user') ? [
                'id' => $booking->user->id,
                'name' => $booking->user->name,
                'mobile' => $booking->user->mobile,
                'email' => $booking->user->email,
                'avatar' => $booking->user->avatar ? asset('storage/'.$booking->user->avatar) : null,
            ] : null;
            $data['payment'] = $booking->relationLoaded('payment') && $booking->payment ? [
                'payment_id' => $booking->payment->payment_id,
                'method' => self::paymentMethodLabel($booking->payment->method),
                'status' => strtoupper($booking->payment->status->value),
                'amount' => (float) $booking->payment->amount,
            ] : null;
        }

        return $data;
    }

    public static function service(Service $service, ServiceProvider $provider, bool $detailed = false): array
    {
        $pivot = $service->pivot;
        $price = (float) ($pivot->price ?? $service->starting_price);
        $rating = round((float) ($provider->reviews_avg_rating ?? $provider->rating ?? 0), 1);
        $reviewsCount = (int) ($provider->reviews_count ?? 0);
        $primaryImage = $service->images->firstWhere('is_primary', true) ?? $service->images->first();

        $data = [
            'id' => $service->id,
            'name' => $service->name,
            'slug' => $service->slug,
            'description' => $service->description,
            'category' => $service->category?->name,
            'category_id' => $service->service_category_id,
            'price' => $price,
            'is_available' => (bool) ($pivot->is_available ?? true),
            'availability_label' => ($pivot->is_available ?? true) ? 'Available' : 'Unavailable',
            'image' => $primaryImage
                ? asset('storage/'.$primaryImage->image_path)
                : ($service->image ? asset('storage/'.$service->image) : null),
            'images' => $service->relationLoaded('images')
                ? $service->images->map(fn ($img) => [
                    'id' => $img->id,
                    'url' => asset('storage/'.$img->image_path),
                    'is_primary' => (bool) $img->is_primary,
                ])->values()
                : [],
            'rating' => $rating,
            'reviews_count' => $reviewsCount,
        ];

        if ($detailed) {
            $data['reviews'] = $provider->relationLoaded('reviews')
                ? $provider->reviews->take(20)->map(fn (ServiceProviderReview $review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user_name' => $review->relationLoaded('user') ? $review->user->name : null,
                    'created_at' => $review->created_at->diffForHumans(),
                ])->values()
                : [];
        }

        return $data;
    }

    public static function earningsSummary(ServiceProvider $provider, float $totalEarnings, float $walletAmount): array
    {
        return [
            'total_earnings' => round($totalEarnings, 2),
            'wallet_amount' => round($walletAmount, 2),
            'bank' => [
                'bank_name' => $provider->bank_name,
                'masked_account_number' => self::maskAccountNumber($provider->account_number),
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

    public static function withdrawal(ProviderWithdrawal $withdrawal): array
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

        return '**** '.substr($accountNumber, -4);
    }
}
