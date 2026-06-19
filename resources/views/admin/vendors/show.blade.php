@extends('admin.layouts.app')
@section('title', $vendor->shop_name)
@section('page-title', 'Vendor Details')
@section('page-subtitle', 'Shop profile, documents and approval actions')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="form-card lg:col-span-2">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $vendor->shop_name }}</h2>
            @include('admin.partials.status-badge', ['status' => $vendor->status])
        </div>
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            <div><dt class="admin-label">Owner</dt><dd class="mt-1 font-semibold">{{ $vendor->owner_name }}</dd></div>
            <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $vendor->mobile }}</dd></div>
            <div><dt class="admin-label">GST</dt><dd class="mt-1">{{ $vendor->gst_number ?? '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="admin-label">Address</dt><dd class="mt-1">{{ $vendor->address ?? '—' }}</dd></div>
        </dl>
        <div class="mt-6 flex flex-wrap gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-secondary btn-sm">Edit</a>
            @if($vendor->status->value === 'pending')
                <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" class="flex flex-wrap items-center gap-2">@csrf<input name="reason" required placeholder="Rejection reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input !h-[34px] max-w-xs"><button class="btn btn-sm bg-red-600 text-white">Reject</button></form>
            @elseif($vendor->status->value === 'approved')
                <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST">@csrf<button class="btn btn-sm border border-red-200 text-red-600">Suspend</button></form>
            @endif
        </div>
    </div>
    <div class="detail-panel">
        <h3 class="detail-panel-title">Documents</h3>
        @forelse($vendor->documents as $doc)
            <div class="detail-row"><span>{{ $doc->document_type }}</span>@if($doc->is_verified)<span class="badge badge-success">Verified</span>@else<span class="badge badge-warning">Pending</span>@endif</div>
        @empty
            <p class="text-sm text-slate-400">No documents uploaded.</p>
        @endforelse
    </div>
</div>
@endsection
