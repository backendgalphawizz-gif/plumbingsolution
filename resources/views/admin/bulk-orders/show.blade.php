@extends('admin.layouts.app')
@section('title', $bulkOrder->reference_number)
@section('page-title', 'Bulk Order Details')
@section('page-subtitle', 'Requirements, files and quotation workflow')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="form-card">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $bulkOrder->reference_number }}</h2>
            @include('admin.partials.status-badge', ['status' => $bulkOrder->status])
        </div>
        <p class="mb-2 text-sm"><span class="font-semibold text-slate-700">Customer:</span> {{ $bulkOrder->user?->name }}</p>
        <p class="mb-5 text-sm text-slate-600">{{ $bulkOrder->requirement_description ?? 'No description provided.' }}</p>
        <h3 class="detail-panel-title !mb-3 !pb-0 !border-0">Uploaded Files</h3>
        @forelse($bulkOrder->files as $f)
            <div class="detail-row"><span>{{ $f->original_name ?? basename($f->file_path) }}</span><span class="text-slate-400">{{ $f->file_type }}</span></div>
        @empty
            <p class="text-sm text-slate-400">No files uploaded.</p>
        @endforelse
        <form action="{{ route('admin.bulk-orders.review', $bulkOrder) }}" method="POST" class="mt-5 border-t border-slate-100 pt-5">@csrf
            <label class="admin-label">Admin Notes</label>
            <textarea name="admin_notes" placeholder="Review notes..." maxlength="{{ config('admin.limits.notes') }}" class="admin-input mb-3">{{ $bulkOrder->admin_notes }}</textarea>
            <button class="btn btn-secondary btn-sm">Save Review</button>
        </form>
    </div>

    <div class="form-card">
        <div class="form-section-title">Quotations</div>
        @forelse($bulkOrder->quotations as $q)
            <div class="mb-3 rounded-xl border border-slate-200 p-4 text-sm">
                <p class="font-semibold text-slate-800">{{ $q->quotation_number }} — ₹{{ number_format($q->amount, 2) }}</p>
                <p class="mt-1 text-slate-500 capitalize">{{ $q->status }}</p>
                @if($q->status==='draft')
                    <form action="{{ route('admin.bulk-orders.quotations.send', [$bulkOrder, $q]) }}" method="POST" class="mt-2">@csrf<button class="action-btn">Send to Customer</button></form>
                @endif
            </div>
        @empty
            <p class="mb-4 text-sm text-slate-400">No quotations yet.</p>
        @endforelse
        <form action="{{ route('admin.bulk-orders.quotations.store', $bulkOrder) }}" method="POST" class="border-t border-slate-100 pt-5">@csrf
            <div class="space-y-3">
                <input type="number" step="0.01" name="amount" required placeholder="Quotation amount" class="admin-input">
                <textarea name="details" placeholder="Quotation details" maxlength="{{ config('admin.limits.quotation_details') }}" class="admin-input" rows="3"></textarea>
            </div>
            <button class="btn btn-primary btn-sm mt-4">Create Quotation</button>
        </form>
    </div>
</div>
@endsection
