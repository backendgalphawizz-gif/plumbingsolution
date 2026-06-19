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
    </dl>
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
