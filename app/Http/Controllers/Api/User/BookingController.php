<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\BookingStatus;
use App\Enums\ProviderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Service;
use App\Models\ServiceBooking;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filter = $request->get('status', 'all');

        $bookings = $request->user()->serviceBookings()
            ->with(['serviceProvider', 'service'])
            ->when($filter !== 'all', function ($q) use ($filter) {
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

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => ['required', 'exists:services,id'],
            'address' => V::addressRules(),
            'scheduled_at' => ['required', 'date', 'after:now'],
            'notes' => V::notesRules(),
        ]);

        $service = Service::where('status', true)->findOrFail($data['service_id']);
        $provider = ServiceProvider::where('status', ProviderStatus::Approved)->inRandomOrder()->first();

        $booking = ServiceBooking::create([
            'booking_number' => 'BK-'.strtoupper(Str::random(8)),
            'user_id' => $request->user()->id,
            'service_id' => $service->id,
            'service_provider_id' => $provider?->id,
            'service_name' => $service->name,
            'description' => $service->description,
            'address' => $data['address'] ?? $request->user()->address,
            'scheduled_at' => Carbon::parse($data['scheduled_at']),
            'status' => $provider ? BookingStatus::Assigned : BookingStatus::Pending,
            'amount' => $service->starting_price,
            'notes' => $data['notes'] ?? null,
        ]);

        $booking->load(['serviceProvider', 'service']);

        return $this->success(UserApiFormatter::booking($booking), 'Booking created.', 201);
    }

    public function show(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);
        $serviceBooking->load(['serviceProvider', 'service']);

        return $this->success(UserApiFormatter::booking($serviceBooking));
    }

    public function cancel(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);

        if (in_array($serviceBooking->status, [BookingStatus::Completed, BookingStatus::Cancelled])) {
            return $this->error('This booking cannot be cancelled.', 422);
        }

        $serviceBooking->update(['status' => BookingStatus::Cancelled]);

        return $this->success(UserApiFormatter::booking($serviceBooking->fresh()), 'Booking cancelled.');
    }

    public function reschedule(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        abort_if($serviceBooking->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $serviceBooking->update(['scheduled_at' => Carbon::parse($data['scheduled_at'])]);

        return $this->success(UserApiFormatter::booking($serviceBooking->fresh()->load('serviceProvider')), 'Booking rescheduled.');
    }

    public function availableSlots(Request $request): JsonResponse
    {
        $request->validate(['date' => ['required', 'date', 'after_or_equal:today']]);

        $date = Carbon::parse($request->date);
        $slots = [];

        foreach (['09:00', '10:00', '11:00', '14:00', '15:00', '16:00'] as $time) {
            $slots[] = [
                'label' => $date->format('D').' '.$time,
                'datetime' => $date->format('Y-m-d').' '.$time.':00',
            ];
        }

        return $this->success($slots);
    }
}
