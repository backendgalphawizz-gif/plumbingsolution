@extends('admin.layouts.app')
@section('title', $serviceBooking->booking_number)
@section('page-title', 'Booking Details')
@section('page-subtitle', 'Assign provider and update booking status')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="form-card lg:col-span-2">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $serviceBooking->service_name }}</h2>
            @include('admin.partials.status-badge', ['status' => $serviceBooking->status])
        </div>
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="admin-label">Booking #</dt><dd class="mt-1 font-semibold">{{ $serviceBooking->booking_number }}</dd></div>
            <div><dt class="admin-label">Customer</dt><dd class="mt-1">{{ $serviceBooking->user?->name }}</dd></div>
            <div><dt class="admin-label">Scheduled</dt><dd class="mt-1">{{ $serviceBooking->scheduled_at?->format('M d, Y H:i') ?? '—' }}</dd></div>
            <div><dt class="admin-label">Amount</dt><dd class="mt-1 font-bold text-emerald-600">₹{{ number_format($serviceBooking->amount, 2) }}</dd></div>
            <div class="sm:col-span-2"><dt class="admin-label">Address</dt><dd class="mt-1">{{ $serviceBooking->address }}</dd></div>
        </dl>
    </div>

    <div class="space-y-4">
        <form action="{{ route('admin.service-bookings.assign', $serviceBooking) }}" method="POST" class="form-card">@csrf
            <div class="form-section-title">Assign Provider</div>
            <select name="service_provider_id" required class="admin-input mb-4">
                <option value="">Select provider</option>
                @foreach($providers as $p)<option value="{{ $p->id }}" @selected($serviceBooking->service_provider_id==$p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <button class="btn btn-primary w-full">Assign Provider</button>
        </form>
        <form action="{{ route('admin.service-bookings.update-status', $serviceBooking) }}" method="POST" class="form-card">@csrf @method('PUT')
            <div class="form-section-title">Update Status</div>
            <select name="status" class="admin-input mb-4">@foreach(['pending','assigned','accepted','started','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($serviceBooking->status->value==$s)>{{ ucfirst($s) }}</option>@endforeach</select>
            <button class="btn btn-secondary w-full">Update Status</button>
        </form>
    </div>
</div>
@endsection
