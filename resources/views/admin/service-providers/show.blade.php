@extends('admin.layouts.app')
@section('title', $serviceProvider->name)
@section('page-title', 'Provider Details')
@section('page-subtitle', 'Profile, skills and approval workflow')

@section('content')
<div class="form-card mx-auto max-w-3xl">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="user-cell">
            <div class="user-avatar">{{ strtoupper(substr($serviceProvider->name, 0, 1)) }}</div>
            <h2 class="text-lg font-bold text-slate-900">{{ $serviceProvider->name }}</h2>
        </div>
        @include('admin.partials.status-badge', ['status' => $serviceProvider->status])
    </div>
    <dl class="mb-6 grid gap-4 text-sm sm:grid-cols-2">
        <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $serviceProvider->mobile }}</dd></div>
        <div><dt class="admin-label">Experience</dt><dd class="mt-1">{{ $serviceProvider->experience_years }} years</dd></div>
        <div class="sm:col-span-2"><dt class="admin-label">Service Area</dt><dd class="mt-1">{{ $serviceProvider->service_area ?? '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="admin-label">Skills</dt><dd class="mt-1">{{ implode(', ', $serviceProvider->skills ?? []) ?: '—' }}</dd></div>
        @if($serviceProvider->account_number)
            <div class="sm:col-span-2 border-t border-slate-100 pt-4"><dt class="admin-label mb-2">Bank Details</dt></div>
            <div><dt class="admin-label">Account Holder</dt><dd class="mt-1">{{ $serviceProvider->account_holder_name }}</dd></div>
            <div><dt class="admin-label">Account Number</dt><dd class="mt-1">{{ $serviceProvider->account_number }}</dd></div>
            <div><dt class="admin-label">IFSC Code</dt><dd class="mt-1">{{ $serviceProvider->ifsc_code }}</dd></div>
            <div><dt class="admin-label">Bank Name</dt><dd class="mt-1">{{ $serviceProvider->bank_name }}</dd></div>
            <div><dt class="admin-label">Account Type</dt><dd class="mt-1 capitalize">{{ $serviceProvider->account_type }}</dd></div>
        @endif
    </dl>
    @if($serviceProvider->documents->isNotEmpty())
        <div class="mb-6 border-t border-slate-100 pt-5">
            <h3 class="admin-label mb-3">Documents</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach($serviceProvider->documents as $document)
                    <a href="{{ asset('storage/'.$document->file_path) }}" target="_blank" class="rounded-lg border border-slate-200 p-3 text-sm font-medium text-emerald-700 hover:bg-emerald-50">
                        {{ str_replace('_', ' ', ucwords($document->document_type, '_')) }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
    @if($serviceProvider->services->isNotEmpty())
        <div class="mb-6 border-t border-slate-100 pt-5">
            <h3 class="admin-label mb-3">Services ({{ $serviceProvider->services->count() }})</h3>
            <div class="space-y-2">
                @foreach($serviceProvider->services as $service)
                    <a href="{{ route('admin.services.show', $service) }}" class="flex items-center justify-between rounded-lg border border-slate-200 p-3 text-sm hover:bg-slate-50">
                        <span class="font-medium">{{ $service->name }}</span>
                        <span class="font-semibold text-emerald-700">₹{{ number_format($service->pivot->price ?? $service->starting_price, 2) }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
    <div class="flex flex-wrap gap-2 border-t border-slate-100 pt-5">
        <a href="{{ route('admin.service-providers.edit', $serviceProvider) }}" class="btn btn-secondary btn-sm">Edit</a>
        @if($serviceProvider->status->value === 'pending')
            <form action="{{ route('admin.service-providers.approve', $serviceProvider) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
            <form action="{{ route('admin.service-providers.reject', $serviceProvider) }}" method="POST" class="flex flex-wrap items-center gap-2">@csrf<input name="reason" required placeholder="Reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input !h-[34px] max-w-xs"><button class="btn btn-sm bg-red-600 text-white">Reject</button></form>
        @elseif($serviceProvider->status->value === 'approved')
            <form action="{{ route('admin.service-providers.suspend', $serviceProvider) }}" method="POST">@csrf<button class="btn btn-sm border border-red-200 text-red-600">Suspend</button></form>
        @endif
    </div>
</div>
@endsection
