@extends('admin.layouts.app')
@section('title', $serviceBooking->booking_number)
@section('page-title', 'Booking Details')
@section('page-subtitle', 'Service booking progress, customer & provider details')

@section('content')
@php
    $bookingSteps = [
        ['key' => 'pending', 'label' => 'Pending'],
        ['key' => 'assigned', 'label' => 'Assigned'],
        ['key' => 'accepted', 'label' => 'Accepted'],
        ['key' => 'started', 'label' => 'Started'],
        ['key' => 'completed', 'label' => 'Completed'],
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="detail-header !mb-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $serviceBooking->service_name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $serviceBooking->booking_number }} · Booked {{ $serviceBooking->created_at->format('M d, Y • g:i A') }}</p>
                </div>
                @include('admin.partials.status-badge', ['status' => $serviceBooking->status])
            </div>
        </div>

        <div class="detail-panel">
            <h3 class="detail-panel-title">Booking Progress</h3>
            @include('admin.partials.status-timeline', [
                'steps' => $bookingSteps,
                'current' => $serviceBooking->status,
                'terminal' => ['cancelled'],
                'entityLabel' => 'booking',
                'logs' => $serviceBooking->logs,
            ])
        </div>

        <div class="detail-panel">
            <h3 class="detail-panel-title">Booking Information</h3>
            <dl class="detail-dl">
                <div><dt class="admin-label">Scheduled</dt><dd class="mt-1 font-semibold">{{ $serviceBooking->scheduled_at?->format('M d, Y • g:i A') ?? '—' }}</dd></div>
                @if($serviceBooking->rescheduled_at)
                    <div><dt class="admin-label">Rescheduled</dt><dd class="mt-1">{{ $serviceBooking->rescheduled_at->format('M d, Y • g:i A') }}</dd></div>
                @endif
                @if($serviceBooking->completed_at)
                    <div><dt class="admin-label">Completed</dt><dd class="mt-1 text-emerald-700">{{ $serviceBooking->completed_at->format('M d, Y • g:i A') }}</dd></div>
                @endif
                <div><dt class="admin-label">Service Amount</dt><dd class="mt-1 text-lg font-bold text-emerald-700">₹{{ number_format($serviceBooking->amount, 2) }}</dd></div>
                @if($serviceBooking->subtotal && $serviceBooking->subtotal != $serviceBooking->amount)
                    <div><dt class="admin-label">Subtotal</dt><dd class="mt-1">₹{{ number_format($serviceBooking->subtotal, 2) }}</dd></div>
                @endif
                @if($serviceBooking->discount_amount > 0)
                    <div><dt class="admin-label">Discount</dt><dd class="mt-1 text-emerald-600">− ₹{{ number_format($serviceBooking->discount_amount, 2) }}</dd></div>
                @endif
                @if($serviceBooking->coupon_code)
                    <div><dt class="admin-label">Coupon</dt><dd class="mt-1">{{ $serviceBooking->coupon_code }}</dd></div>
                @endif
                <div class="span-full"><dt class="admin-label">Service Address</dt><dd class="mt-1 text-slate-600">{{ $serviceBooking->address }}</dd></div>
                @if($serviceBooking->description)
                    <div class="span-full"><dt class="admin-label">Issue Description</dt><dd class="mt-1 text-slate-600">{{ $serviceBooking->description }}</dd></div>
                @endif
                @if($serviceBooking->notes)
                    <div class="span-full"><dt class="admin-label">Notes</dt><dd class="mt-1 text-slate-600">{{ $serviceBooking->notes }}</dd></div>
                @endif
            </dl>
        </div>

        @if($serviceBooking->images->isNotEmpty())
            <div class="detail-panel">
                <h3 class="detail-panel-title">Issue Photos</h3>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach($serviceBooking->images as $image)
                        <a href="{{ asset('storage/'.$image->image_path) }}" target="_blank" class="block overflow-hidden rounded-lg border border-slate-200">
                            <img src="{{ asset('storage/'.$image->image_path) }}" alt="Booking photo" class="h-28 w-full object-cover">
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($serviceBooking->status->value === 'cancelled' && $serviceBooking->cancellation_reason)
            <div class="detail-panel border-red-200 bg-red-50/50">
                <h3 class="detail-panel-title text-red-700">Cancellation</h3>
                <p class="text-sm text-red-800">{{ $serviceBooking->cancellation_reason }}</p>
            </div>
        @endif

        @if($serviceBooking->review)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Customer Review</h3>
                <p class="text-sm font-semibold text-amber-600">{{ $serviceBooking->review->rating }} / 5</p>
                @if($serviceBooking->review->comment)
                    <p class="mt-2 text-sm text-slate-600">{{ $serviceBooking->review->comment }}</p>
                @endif
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @include('admin.partials.entity-user-card', [
            'user' => $serviceBooking->user,
            'ordersCount' => $serviceBooking->user?->orders_count,
            'bookingsCount' => $serviceBooking->user?->service_bookings_count,
        ])

        <div class="detail-panel">
            <h3 class="detail-panel-title">Assigned Provider</h3>
            @if($serviceBooking->serviceProvider)
                <div class="user-cell mb-3">
                    <div class="user-avatar">{{ strtoupper(substr($serviceBooking->serviceProvider->name, 0, 1)) }}</div>
                    <div>
                        <div class="user-name">{{ $serviceBooking->serviceProvider->name }}</div>
                        <div class="user-sub">{{ $serviceBooking->serviceProvider->mobile }}</div>
                    </div>
                </div>
                <p class="mb-3 text-sm text-slate-600">{{ $serviceBooking->serviceProvider->service_area ?? 'No service area' }}</p>
                <a href="{{ route('admin.service-providers.show', $serviceBooking->serviceProvider) }}" class="action-btn inline-flex">View Provider</a>
            @else
                <p class="text-sm text-slate-400">No provider assigned yet.</p>
            @endif
        </div>

        @if($serviceBooking->payment)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Payment</h3>
                <dl class="detail-dl">
                    <div><dt class="admin-label">Method</dt><dd class="mt-1 capitalize">{{ $serviceBooking->payment->method->value ?? $serviceBooking->payment->method }}</dd></div>
                    <div><dt class="admin-label">Status</dt><dd class="mt-1">@include('admin.partials.status-badge', ['status' => $serviceBooking->payment->status->value ?? $serviceBooking->payment->status])</dd></div>
                    <div><dt class="admin-label">Amount</dt><dd class="mt-1 font-semibold text-emerald-700">₹{{ number_format($serviceBooking->payment->amount, 2) }}</dd></div>
                </dl>
            </div>
        @endif

        <form action="{{ route('admin.service-bookings.assign', $serviceBooking) }}" method="POST" class="form-card">@csrf
            <div class="form-section-title">Assign Provider</div>
            <select name="service_provider_id" required class="admin-input mb-4">
                <option value="">Select provider</option>
                @foreach($providers as $p)
                    <option value="{{ $p->id }}" @selected($serviceBooking->service_provider_id==$p->id)>{{ $p->name }} · {{ $p->mobile }}</option>
                @endforeach
            </select>
            <button class="btn btn-primary w-full">Assign Provider</button>
        </form>

        <form action="{{ route('admin.service-bookings.update-status', $serviceBooking) }}" method="POST" class="form-card">@csrf @method('PUT')
            <div class="form-section-title">Update Status</div>
            <select name="status" class="admin-input mb-3">
                @foreach(['pending','assigned','accepted','started','completed','cancelled'] as $s)
                    <option value="{{ $s }}" @selected($serviceBooking->status->value==$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <textarea name="notes" placeholder="Notes for status history (optional)" maxlength="{{ config('admin.limits.notes') }}" class="admin-input mb-4" rows="2"></textarea>
            <button class="btn btn-secondary w-full">Update Status</button>
        </form>
    </div>
</div>
@endsection
