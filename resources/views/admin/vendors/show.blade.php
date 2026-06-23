@extends('admin.layouts.app')
@section('title', $vendor->shop_name)
@section('page-title', 'Vendor Details')
@section('page-subtitle', 'Shop profile, documents, orders and approval actions')

@section('content')
<div class="detail-header">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex gap-4">
            @if($vendor->shop_logo)
                <img src="{{ asset('storage/'.$vendor->shop_logo) }}" alt="" class="h-16 w-16 rounded-xl border border-slate-200 object-cover">
            @else
                <div class="user-avatar !h-16 !w-16 !text-xl">{{ strtoupper(substr($vendor->shop_name, 0, 1)) }}</div>
            @endif
            <div>
                <h2 class="text-xl font-bold text-slate-900">{{ $vendor->shop_name }}</h2>
                <p class="text-sm text-slate-500">{{ $vendor->owner_name }} · {{ $vendor->mobile }}</p>
                <div class="mt-2">@include('admin.partials.status-badge', ['status' => $vendor->status])</div>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-secondary btn-sm">Edit Vendor</a>
            @if($vendor->status->value === 'pending')
                <form action="{{ route('admin.vendors.approve', $vendor) }}" method="POST">@csrf<button class="btn btn-primary btn-sm">Approve</button></form>
                <form action="{{ route('admin.vendors.reject', $vendor) }}" method="POST" class="flex flex-wrap items-center gap-2">@csrf
                    <input name="reason" required placeholder="Rejection reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input !h-[34px] max-w-xs">
                    <button class="btn btn-sm bg-red-600 text-white">Reject</button>
                </form>
            @elseif($vendor->status->value === 'approved')
                <form action="{{ route('admin.vendors.suspend', $vendor) }}" method="POST">@csrf<button class="btn btn-sm border border-red-200 text-red-600">Suspend</button></form>
            @endif
        </div>
    </div>
</div>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="detail-panel text-center"><p class="text-2xl font-bold">{{ $vendor->products_count }}</p><p class="text-xs font-semibold uppercase text-slate-500">Products</p></div>
    <div class="detail-panel text-center"><p class="text-2xl font-bold">{{ $vendor->orders_count }}</p><p class="text-xs font-semibold uppercase text-slate-500">Orders</p></div>
    <div class="detail-panel text-center"><p class="text-sm font-semibold">{{ $vendor->approved_at?->format('M d, Y') ?? '—' }}</p><p class="text-xs font-semibold uppercase text-slate-500">Approved On</p></div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="detail-panel">
            <h3 class="detail-panel-title">Owner & Contact</h3>
            <dl class="detail-dl">
                <div><dt class="admin-label">Owner Name</dt><dd class="mt-1 font-semibold">{{ $vendor->owner_name }}</dd></div>
                <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $vendor->mobile }}</dd></div>
                <div><dt class="admin-label">Business Mobile</dt><dd class="mt-1">{{ $vendor->business_mobile ?? '—' }}</dd></div>
                <div><dt class="admin-label">Email</dt><dd class="mt-1">{{ $vendor->email ?? '—' }}</dd></div>
            </dl>
        </div>

        <div class="detail-panel">
            <h3 class="detail-panel-title">Shop & Location</h3>
            <dl class="detail-dl">
                <div class="span-full"><dt class="admin-label">Address</dt><dd class="mt-1 text-slate-600">{{ $vendor->address ?? '—' }}</dd></div>
                <div><dt class="admin-label">City</dt><dd class="mt-1">{{ $vendor->city ?? '—' }}</dd></div>
                <div><dt class="admin-label">State</dt><dd class="mt-1">{{ $vendor->state ?? '—' }}</dd></div>
                <div><dt class="admin-label">Pincode</dt><dd class="mt-1">{{ $vendor->pincode ?? '—' }}</dd></div>
                <div><dt class="admin-label">Country</dt><dd class="mt-1">{{ $vendor->country ?? '—' }}</dd></div>
                <div><dt class="admin-label">GST Number</dt><dd class="mt-1">{{ $vendor->gst_number ?? '—' }}</dd></div>
            </dl>
        </div>

        @if($vendor->account_number)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Bank Details</h3>
                <dl class="detail-dl">
                    <div><dt class="admin-label">Account Holder</dt><dd class="mt-1">{{ $vendor->account_holder_name }}</dd></div>
                    <div><dt class="admin-label">Account Number</dt><dd class="mt-1">{{ $vendor->account_number }}</dd></div>
                    <div><dt class="admin-label">IFSC</dt><dd class="mt-1">{{ $vendor->ifsc_code }}</dd></div>
                    <div><dt class="admin-label">Bank</dt><dd class="mt-1">{{ $vendor->bank_name }}</dd></div>
                    <div><dt class="admin-label">Account Type</dt><dd class="mt-1 capitalize">{{ $vendor->account_type }}</dd></div>
                </dl>
            </div>
        @endif

        <div class="detail-panel">
            <h3 class="detail-panel-title">Recent Orders</h3>
            @forelse($recentOrders as $order)
                <a href="{{ route('admin.orders.show', $order) }}" class="detail-row hover:bg-slate-50 -mx-2 px-2 rounded-lg">
                    <div>
                        <span class="font-medium">{{ $order->order_number }}</span>
                        <p class="text-xs text-slate-400">{{ $order->user?->name }} · {{ $order->created_at->format('M d, Y') }}</p>
                    </div>
                    <div class="text-right">
                        <span class="font-semibold text-emerald-600">₹{{ number_format($order->total_amount, 2) }}</span>
                        <div class="mt-1">@include('admin.partials.status-badge', ['status' => $order->status])</div>
                    </div>
                </a>
            @empty
                <p class="text-sm text-slate-400">No orders yet.</p>
            @endforelse
        </div>
    </div>

    <div class="detail-panel">
        <h3 class="detail-panel-title">Documents</h3>
        @forelse($vendor->documents as $doc)
            <div class="mb-3 rounded-lg border border-slate-200 p-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-medium capitalize">{{ str_replace('_', ' ', $doc->document_type) }}</span>
                    @if($doc->is_verified)<span class="badge badge-success">Verified</span>@else<span class="badge badge-warning">Pending</span>@endif
                </div>
                @if($doc->file_path)
                    <a href="{{ asset('storage/'.$doc->file_path) }}" target="_blank" class="action-btn mt-2 inline-flex">View File</a>
                @endif
            </div>
        @empty
            <p class="text-sm text-slate-400">No documents uploaded.</p>
        @endforelse

        @if($vendor->status->value === 'rejected' && $vendor->rejection_reason)
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                <strong>Rejection reason:</strong> {{ $vendor->rejection_reason }}
            </div>
        @endif
    </div>
</div>
@endsection
