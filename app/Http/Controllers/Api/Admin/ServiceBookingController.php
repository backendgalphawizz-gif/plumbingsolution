<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BookingLog;
use App\Models\ServiceBooking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceBookingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $bookings = ServiceBooking::with(['user:id,name', 'serviceProvider:id,name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('booking_number', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($bookings);
    }

    public function show(ServiceBooking $serviceBooking): JsonResponse
    {
        return $this->success($serviceBooking->load(['user', 'serviceProvider', 'logs.changedBy', 'images']));
    }

    public function assignProvider(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        $request->validate(['service_provider_id' => ['required', 'exists:service_providers,id']]);

        $serviceBooking->update([
            'service_provider_id' => $request->service_provider_id,
            'status' => BookingStatus::Assigned,
        ]);

        $this->logStatus($serviceBooking, BookingStatus::Assigned->value, 'Provider assigned.', $request->user()->id);

        return $this->success($serviceBooking->fresh()->load('serviceProvider'), 'Provider assigned.');
    }

    public function updateStatus(Request $request, ServiceBooking $serviceBooking): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string'],
        ]);

        $updates = ['status' => $request->status];

        if ($request->status === BookingStatus::Completed->value) {
            $updates['completed_at'] = now();
        }

        $serviceBooking->update($updates);
        $this->logStatus($serviceBooking, $request->status, $request->notes, $request->user()->id);

        return $this->success($serviceBooking->fresh()->load('logs'), 'Booking status updated.');
    }

    private function logStatus(ServiceBooking $booking, string $status, ?string $notes, int $adminId): void
    {
        BookingLog::create([
            'service_booking_id' => $booking->id,
            'status' => $status,
            'notes' => $notes,
            'changed_by' => $adminId,
        ]);
    }
}
