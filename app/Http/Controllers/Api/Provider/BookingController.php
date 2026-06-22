<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\BookingStatus;
use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BookingLog;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $request->validate([
            'status' => ['nullable', 'in:all,new,accepted,ongoing,rescheduled,completed,cancelled'],
            'search' => V::searchRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $bookings = $provider->bookings()
            ->with(['user', 'images', 'payment'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('booking_number', 'like', "%{$s}%")
                    ->orWhere('service_name', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
                match ($request->status) {
                    'new' => $q->whereIn('status', [BookingStatus::Pending, BookingStatus::Assigned]),
                    'accepted' => $q->where('status', BookingStatus::Accepted),
                    'ongoing' => $q->where('status', BookingStatus::Started),
                    'rescheduled' => $q->whereNotNull('rescheduled_at')
                        ->whereNotIn('status', [BookingStatus::Cancelled, BookingStatus::Completed]),
                    'completed' => $q->where('status', BookingStatus::Completed),
                    'cancelled' => $q->where('status', BookingStatus::Cancelled),
                    default => null,
                };
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($bookings->items())->map(fn ($booking) => ProviderApiFormatter::booking($booking, detailed: true))->values(),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Request $request, int $booking): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $bookingModel = $this->providerBooking($provider, $booking);

        if (! $bookingModel) {
            return $this->error('Booking not found.', 404);
        }

        return $this->success(ProviderApiFormatter::booking($bookingModel, detailed: true));
    }

    public function accept(Request $request, int $booking): JsonResponse
    {
        return $this->updateStatus($request, $booking, BookingStatus::Accepted, 'Booking accepted by provider.');
    }

    public function reject(Request $request, int $booking): JsonResponse
    {
        $request->validate(['reason' => V::reasonRules(required: false)]);

        return $this->updateStatus(
            $request,
            $booking,
            BookingStatus::Cancelled,
            $request->input('reason', 'Rejected by provider.'),
            onlyFrom: BookingStatus::Assigned,
        );
    }

    public function start(Request $request, int $booking): JsonResponse
    {
        return $this->updateStatus($request, $booking, BookingStatus::Started, 'Service started by provider.', BookingStatus::Accepted);
    }

    public function complete(Request $request, int $booking): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $bookingModel = $this->providerBooking($provider, $booking);

        if (! $bookingModel) {
            return $this->error('Booking not found.', 404);
        }

        if ($bookingModel->status !== BookingStatus::Started) {
            return $this->error('Only ongoing bookings can be completed.', 422);
        }

        $bookingModel->update([
            'status' => BookingStatus::Completed,
            'completed_at' => now(),
        ]);

        $this->logStatus($bookingModel->id, BookingStatus::Completed->value, 'Service completed by provider.');

        return $this->success(
            ProviderApiFormatter::booking($bookingModel->fresh()->load(['user', 'images', 'payment']), detailed: true),
            'Service completed.'
        );
    }

    private function updateStatus(
        Request $request,
        int $bookingId,
        BookingStatus $status,
        string $notes,
        ?BookingStatus $onlyFrom = null,
    ): JsonResponse {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $booking = $this->providerBooking($provider, $bookingId);

        if (! $booking) {
            return $this->error('Booking not found.', 404);
        }

        $requiredStatus = $onlyFrom ?? BookingStatus::Assigned;

        if ($booking->status !== $requiredStatus) {
            return $this->error('This booking cannot be updated in its current status.', 422);
        }

        $booking->update(['status' => $status]);
        $this->logStatus($booking->id, $status->value, $notes);

        return $this->success(
            ProviderApiFormatter::booking($booking->fresh()->load(['user', 'images', 'payment']), detailed: true),
            'Booking updated.'
        );
    }

    private function logStatus(int $bookingId, string $status, string $notes): void
    {
        BookingLog::create([
            'service_booking_id' => $bookingId,
            'status' => $status,
            'notes' => $notes,
        ]);
    }
}
