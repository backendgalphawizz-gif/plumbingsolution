@extends('admin.layouts.app')
@section('title', $serviceProvider->name)
@section('page-title', 'Provider Details')
@section('page-subtitle', 'Profile, skills, services, bookings and approval workflow')

@section('content')
<div class="detail-header">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="user-cell">
            @if($serviceProvider->avatar)
                <img src="{{ asset('storage/'.$serviceProvider->avatar) }}" alt="" class="user-avatar !rounded-full !object-cover">
            @else
                <div class="user-avatar">{{ strtoupper(substr($serviceProvider->name, 0, 1)) }}</div>
            @endif
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $serviceProvider->name }}</h2>
                <p class="text-sm text-slate-500">{{ $serviceProvider->mobile }} · {{ $serviceProvider->experience_years }} yrs experience</p>
                <div class="mt-2">@include('admin.partials.status-badge', ['status' => $serviceProvider->status])</div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.service-providers.edit', $serviceProvider) }}" class="btn btn-secondary btn-sm">Edit Provider</a>
            @if($serviceProvider->status->value === 'pending')
                <form action="{{ route('admin.service-providers.approve', $serviceProvider) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                <form action="{{ route('admin.service-providers.reject', $serviceProvider) }}" method="POST" class="flex flex-wrap items-center gap-2">@csrf
                    <input name="reason" required placeholder="Reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input !h-[34px] max-w-xs">
                    <button class="btn btn-sm bg-red-600 text-white">Reject</button>
                </form>
            @elseif($serviceProvider->status->value === 'approved')
                <form action="{{ route('admin.service-providers.suspend', $serviceProvider) }}" method="POST">@csrf<button class="btn btn-sm border border-red-200 text-red-600">Suspend</button></form>
            @endif
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="detail-panel text-center"><p class="text-2xl font-bold">{{ $serviceProvider->bookings_count }}</p><p class="text-xs font-semibold uppercase text-slate-500">Bookings</p></div>
    <div class="detail-panel text-center"><p class="text-2xl font-bold">{{ $serviceProvider->services->count() }}</p><p class="text-xs font-semibold uppercase text-slate-500">Services</p></div>
    <div class="detail-panel text-center"><p class="text-sm font-semibold">{{ $serviceProvider->approved_at?->format('M d, Y') ?? '—' }}</p><p class="text-xs font-semibold uppercase text-slate-500">Approved On</p></div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="detail-panel">
            <h3 class="detail-panel-title">Personal Details</h3>
            <dl class="detail-dl">
                <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $serviceProvider->mobile }}</dd></div>
                <div><dt class="admin-label">Email</dt><dd class="mt-1">{{ $serviceProvider->user?->email ?? '—' }}</dd></div>
                <div><dt class="admin-label">Experience</dt><dd class="mt-1">{{ $serviceProvider->experience_years }} years</dd></div>
                <div><dt class="admin-label">Service Area</dt><dd class="mt-1">{{ $serviceProvider->service_area ?? '—' }}</dd></div>
                <div class="span-full"><dt class="admin-label">Address</dt><dd class="mt-1 text-slate-600">{{ $serviceProvider->address ?? '—' }}</dd></div>
                <div class="span-full"><dt class="admin-label">Skills</dt><dd class="mt-1">{{ implode(', ', $serviceProvider->skills ?? []) ?: '—' }}</dd></div>
            </dl>
        </div>

        @if($serviceProvider->account_number)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Bank Details</h3>
                <dl class="detail-dl">
                    <div><dt class="admin-label">Account Holder</dt><dd class="mt-1">{{ $serviceProvider->account_holder_name }}</dd></div>
                    <div><dt class="admin-label">Account Number</dt><dd class="mt-1">{{ $serviceProvider->account_number }}</dd></div>
                    <div><dt class="admin-label">IFSC</dt><dd class="mt-1">{{ $serviceProvider->ifsc_code }}</dd></div>
                    <div><dt class="admin-label">Bank</dt><dd class="mt-1">{{ $serviceProvider->bank_name }}</dd></div>
                    <div><dt class="admin-label">Account Type</dt><dd class="mt-1 capitalize">{{ $serviceProvider->account_type }}</dd></div>
                </dl>
            </div>
        @endif

        @if($serviceProvider->services->isNotEmpty())
            <div class="detail-panel">
                <h3 class="detail-panel-title">Linked Services</h3>
                @foreach($serviceProvider->services as $service)
                    <a href="{{ route('admin.services.show', $service) }}" class="detail-row hover:bg-slate-50 -mx-2 px-2 rounded-lg">
                        <span class="font-medium">{{ $service->name }}</span>
                        <span class="font-semibold text-emerald-700">₹{{ number_format($service->pivot->price ?? $service->starting_price, 2) }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="detail-panel">
            <h3 class="detail-panel-title">Booking History</h3>
            @forelse($bookings as $booking)
                <a href="{{ route('admin.service-bookings.show', $booking) }}" class="detail-row hover:bg-slate-50 -mx-2 px-2 rounded-lg">
                    <div>
                        <span class="font-medium">{{ $booking->service_name }}</span>
                        <p class="text-xs text-slate-400">{{ $booking->booking_number }} · {{ $booking->user?->name }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-emerald-600">₹{{ number_format($booking->amount, 2) }}</span>
                        <div class="mt-1">@include('admin.partials.status-badge', ['status' => $booking->status])</div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-400">No bookings yet.</p>
            @endforelse
        </div>
    </div>

    <div class="space-y-4">
        @if($serviceProvider->documents->isNotEmpty())
            <div class="detail-panel">
                <h3 class="detail-panel-title">Documents</h3>
                @foreach($serviceProvider->documents as $document)
                    <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="mb-2 flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                        {{ str_replace('_', ' ', ucwords($document->document_type, '_')) }}
                        <span class="text-xs">View</span>
                    </a>
                @endforeach
            </div>
        @endif

        @if($serviceProvider->status->value === 'rejected' && $serviceProvider->rejection_reason)
            <div class="detail-panel border-red-200 bg-red-50/50">
                <h3 class="detail-panel-title text-red-700">Rejection</h3>
                <p class="text-sm text-red-800">{{ $serviceProvider->rejection_reason }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
