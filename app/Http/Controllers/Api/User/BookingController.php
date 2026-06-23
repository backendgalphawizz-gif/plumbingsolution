<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BookingImage;
use App\Models\Payment;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Models\ServiceProviderReview;
use App\Models\Transaction;
use App\Services\CouponService;
use App\Services\PushNotificationService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', Rule::in(['all', 'processing', 'confirmed', 'completed', 'cancelled', 'reschedule'])],
        ]);

        $filter = $request->get('status', 'all');

        $bookings = $request->user()->serviceBookings()
            ->with(['serviceProvider', 'service', 'images', 'payment', 'review.user'])
            ->when($filter === 'reschedule', fn ($q) => $q
                ->whereNotNull('rescheduled_at')
                ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Completed]))
            ->when($filter !== 'all' && $filter !== 'reschedule', function ($q) use ($filter) {
                $map = [
                    'processing' => [BookingStatus::Pending, BookingStatus::Assigned],
                    'confirmed' => [BookingStatus::Accepted, BookingStatus::Started],
                    'completed' => [BookingStatus::Completed],
                    'cancelled' => [BookingStatus::Cancelled],
                ];
                if (isset($map[$filter])) {
                    $q->whereIn('status', array_map(fn ($s) => $s->value, $map[$filter]));
                }
            })
            ->latest()
            ->paginate(15);

        return $this->success([
            'items' => collect($bookings->items())->map(fn ($b) => UserApiFormatter::booking($b)),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function store(Request $request, CouponService $coupons): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'service_provider_id' => ['required', 'exists:service_providers,id'],
            'schedule_date' => ['required_without:scheduled_at', 'date', 'after_or_equal:today'],
            'schedule_time' => ['required_without:scheduled_at', 'date_format:H:i'],
            'scheduled_at' => ['required_without_all:schedule_date,schedule_time', 'date', 'after:now'],
            'address' => ['required', 'string', V::maxRule('address')],
            'promo_code' => ['nullable', 'string', 'max:30'],
            'payment_method' => ['required', 'in:razorpay,cod'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'notes' => V::notesRules(),
            'issue_images' => ['nullable', 'array', 'max:5'],
            'issue_images.*' => ['image', 'max:5120'],
        ]);

        $service = Service::where('status', true)->findOrFail($data['service_id']);

        $provider = ServiceProvider::where('status', ProviderStatus::Approved)
            ->where('id', $data['service_provider_id'])
            ->firstOrFail();

        if (! $provider->services()->where('services.id', $service->id)->exists()) {
            return $this->error('Selected provider does not offer this service.', 422);
        }

        if ($data['payment_method'] === 'razorpay' && empty($data['transaction_id'])) {
            return $this->error('Transaction ID is required for online payment.', 422);
        }

        $scheduledAt = ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'])
            : Carbon::parse($data['schedule_date'].' '.$data['schedule_time']);

        $pricing = $coupons->calculateForService($service, $data['promo_code'] ?? null);

        if (! empty($data['promo_code']) && ! $pricing['coupon_applied']) {
            return $this->error('Invalid or inapplicable promo code.', 422);
        }

        $booking = ServiceBooking::create([
            'booking_number' => 'BK-'.strtoupper(Str::random(8)),
            'user_id' => $request->user()->id,
            'service_id' => $service->id,
            'service_provider_id' => $provider->id,
            'service_name' => $service->name,
            'description' => $service->description,
            'address' => $data['address'],
            'scheduled_at' => $scheduledAt,
            'status' => BookingStatus::Assigned,
            'subtotal' => $pricing['subtotal'],
            'discount_amount' => $pricing['discount'],
            'coupon_code' => $pricing['coupon_code'],
            'amount' => $pricing['total'],
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->hasFile('issue_images')) {
            foreach ($request->file('issue_images') as $image) {
                BookingImage::create([
                    'service_booking_id' => $booking->id,
                    'image_path' => $image->store('bookings/issues', 'public'),
                    'caption' => 'issue',
                ]);
            }
        }

        $paymentStatus = $data['payment_method'] === 'cod' ? PaymentStatus::Pending : PaymentStatus::Completed;

        $payment = Payment::create([
            'payment_id' => 'PAY-'.strtoupper(Str::random(10)),
            'user_id' => $request->user()->id,
            'payable_type' => ServiceBooking::class,
            'payable_id' => $booking->id,
            'method' => PaymentMethod::from($data['payment_method']),
            'status' => $paymentStatus,
            'amount' => $pricing['total'],
            'currency' => 'INR',
            'gateway_payment_id' => $data['transaction_id'] ?? null,
        ]);

        if (! empty($data['transaction_id'])) {
            Transaction::create([
                'payment_id' => $payment->id,
                'transaction_id' => $data['transaction_id'],
                'type' => 'payment',
                'amount' => $pricing['total'],
                'status' => 'completed',
                'description' => 'Service booking payment',
            ]);
        }

        $booking->load(['serviceProvider', 'service', 'images', 'payment']);

        app(PushNotificationService::class)->bookingCreated($booking);

        return $this->success(UserApiFormatter::booking($booking), 'Booking created.', 201);
    }

    public function paymentMethods(): JsonResponse
    {
        return $this->success([
            ['code' => 'razorpay', 'name' => 'Razorpay', 'icon' => 'razorpay'],
            ['code' => 'cod', 'name' => 'Pay on Service', 'icon' => 'cod'],
        ]);
    }

    public function show(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);
        $serviceBooking->load(['serviceProvider', 'service', 'images', 'payment', 'review.user']);

        return $this->success(UserApiFormatter::booking($serviceBooking));
    }

    public function cancel(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);

        if (in_array($serviceBooking->status, [BookingStatus::Completed, BookingStatus::Cancelled])) {
            return $this->error('This booking cannot be cancelled.', 422);
        }

        $data = $request->validate(['reason' => V::reasonRules()]);

        $serviceBooking->update([
            'status' => BookingStatus::Cancelled,
            'cancellation_reason' => $data['reason'],
        ]);

        app(PushNotificationService::class)->bookingStatusUpdated(
            $serviceBooking->fresh(),
            'Booking Cancelled',
            "Booking {$serviceBooking->booking_number} was cancelled by the customer.",
            'booking_cancelled',
        );

        return $this->success(
            UserApiFormatter::booking($serviceBooking->fresh()->load(['serviceProvider', 'images', 'payment'])),
            'Booking cancelled.'
        );
    }

    public function reschedule(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);

        if (! in_array($serviceBooking->status, [BookingStatus::Pending, BookingStatus::Assigned, BookingStatus::Accepted])) {
            return $this->error('This booking cannot be rescheduled.', 422);
        }

        $data = $request->validate([
            'schedule_date' => ['required_without:scheduled_at', 'date', 'after_or_equal:today'],
            'schedule_time' => ['required_without:scheduled_at', 'date_format:H:i'],
            'scheduled_at' => ['required_without_all:schedule_date,schedule_time', 'date', 'after:now'],
        ]);

        $scheduledAt = ! empty($data['scheduled_at'])
            ? Carbon::parse($data['scheduled_at'])
            : Carbon::parse($data['schedule_date'].' '.$data['schedule_time']);

        $serviceBooking->update([
            'scheduled_at' => $scheduledAt,
            'rescheduled_at' => now(),
        ]);

        return $this->success(
            UserApiFormatter::booking($serviceBooking->fresh()->load(['serviceProvider', 'images', 'payment'])),
            'Booking rescheduled.'
        );
    }

    public function review(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);

        if ($serviceBooking->status !== BookingStatus::Completed) {
            return $this->error('You can only review completed bookings.', 422);
        }

        if (! $serviceBooking->service_provider_id) {
            return $this->error('No service provider linked to this booking.', 422);
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if (ServiceProviderReview::where('service_booking_id', $serviceBooking->id)->exists()) {
            return $this->error('You have already reviewed this booking.', 422);
        }

        $review = ServiceProviderReview::create([
            'user_id' => $request->user()->id,
            'service_provider_id' => $serviceBooking->service_provider_id,
            'service_booking_id' => $serviceBooking->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return $this->success(UserApiFormatter::serviceProviderReview($review), 'Review submitted.', 201);
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'after_or_equal:today']]);

        $date = Carbon::parse($request->date);
        $slots = [];

        foreach (['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'] as $time) {
            $slots[] = [
                'label' => $date->format('D').' '.$time,
                'date' => $date->format('Y-m-d'),
                'time' => $time,
                'datetime' => $date->format('Y-m-d').' '.$time.':00',
            ];
        }

        return $this->success($slots);
    }
}
