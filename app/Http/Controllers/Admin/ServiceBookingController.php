<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\BookingLog;
use App\Models\ServiceBooking;
use App\Services\PushNotificationService;
use App\Services\WalletService;
use App\Models\ServiceProvider;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceBookingController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $bookings = $this->filteredBookings($request)->paginate(15)->withQueryString();

        return view('admin.service-bookings.index', compact('bookings'));
    }

    public function export(Request $request)
    {
        $bookings = $this->filteredBookings($request)->get();

        return $this->exportResponse(
            $request,
            'service-bookings',
            'Service Booking List',
            ['Booking #', 'Service', 'Customer', 'Provider', 'Amount', 'Status', 'Created Date'],
            $bookings->map(fn (ServiceBooking $b) => [
                $b->booking_number,
                $b->service_name,
                $b->user?->name ?? '',
                $b->serviceProvider?->name ?? '',
                number_format((float) $b->amount, 2),
                $b->status->value ?? $b->status,
                $b->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredBookings(Request $request): Builder
    {
        return $this->applyDateRange(
            ServiceBooking::with(['user', 'serviceProvider'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, fn ($q, $s) => $q->where('booking_number', 'like', "%{$s}%"))
                ->latest(),
            $request
        );
    }

    public function show(ServiceBooking $serviceBooking): View
    {
        $serviceBooking->load([
            'user' => fn ($q) => $q->withCount(['orders', 'serviceBookings']),
            'serviceProvider',
            'service',
            'payment',
            'review',
            'images',
            'logs' => fn ($q) => $q->latest()->with('changedBy'),
        ]);
        $providers = ServiceProvider::where('status', 'approved')->orderBy('name')->get();

        return view('admin.service-bookings.show', compact('serviceBooking', 'providers'));
    }

    public function assign(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $request->validate(['service_provider_id' => ['required', 'exists:service_providers,id']]);

        $serviceBooking->update([
            'service_provider_id' => $request->service_provider_id,
            'status' => BookingStatus::Assigned,
        ]);

        $this->log($serviceBooking, BookingStatus::Assigned->value, 'Provider assigned.');

        app(PushNotificationService::class)->bookingStatusUpdated(
            $serviceBooking->fresh()->load(['user', 'serviceProvider.user']),
            'Provider Assigned',
            'A service provider has been assigned to your booking.',
            'booking_assigned',
        );

        return back()->with('success', 'Provider assigned.');
    }

    public function updateStatus(Request $request, ServiceBooking $serviceBooking): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(BookingStatus::cases(), 'value'))],
            'notes' => V::notesRules(),
        ]);

        $nextStatus = BookingStatus::from($request->status);

        if (! $serviceBooking->status->canTransitionTo($nextStatus)) {
            return back()->with('error', 'Booking cannot move from '.$serviceBooking->status->label().' to '.$nextStatus->label().'. Follow the next step in the service flow.');
        }

        $wasCompleted = $serviceBooking->status === BookingStatus::Completed;

        $updates = ['status' => $nextStatus];
        if ($nextStatus === BookingStatus::Completed) {
            $updates['completed_at'] = now();
        }

        $serviceBooking->update($updates);
        $this->log($serviceBooking, $nextStatus->value, $request->notes);

        if (! $wasCompleted && $nextStatus === BookingStatus::Completed) {
            $serviceBooking->load('serviceProvider.user');
            if ($serviceBooking->serviceProvider?->user && $serviceBooking->amount > 0) {
                app(WalletService::class)->credit(
                    $serviceBooking->serviceProvider->user,
                    (float) $serviceBooking->amount
                );
            }
        }

        app(PushNotificationService::class)->bookingStatusUpdated(
            $serviceBooking->fresh()->load(['user', 'serviceProvider.user']),
            'Booking Status Updated',
            'Your booking status is now '.$nextStatus->label().'.',
            'booking_status_updated',
        );

        return back()->with('success', 'Booking status updated.');
    }

    private function log(ServiceBooking $booking, string $status, ?string $notes): void
    {
        BookingLog::create([
            'service_booking_id' => $booking->id,
            'status' => $status,
            'notes' => $notes,
            'changed_by' => auth('admin')->id(),
        ]);
    }
}
