@extends('admin.layouts.app')
@section('title', $order->order_number)
@section('page-title', 'Order Details')
@section('page-subtitle', 'Items, status updates and cancellation')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="data-card lg:col-span-2">
        <div class="data-card-header">
            <div>
                <div class="data-card-title">{{ $order->order_number }}</div>
                <div class="data-card-meta">{{ $order->user?->name }} · {{ $order->created_at->format('M d, Y H:i') }}</div>
            </div>
            @include('admin.partials.status-badge', ['status' => $order->status])
        </div>
        <table class="admin-table">
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th></tr></thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr><td>{{ $item->product_name }}</td><td>{{ $item->quantity }}</td><td class="font-semibold">₹{{ number_format($item->total_price, 2) }}</td></tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-4 text-right text-lg font-bold text-slate-900">Total: ₹{{ number_format($order->total_amount, 2) }}</div>
    </div>

    <div class="space-y-4">
        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="form-card">@csrf @method('PUT')
            <div class="form-section-title">Update Status</div>
            <div class="space-y-3">
                <select name="status" class="admin-input">@foreach(['pending','accepted','packed','shipped','delivered','cancelled','returned','refunded'] as $s)<option value="{{ $s }}" @selected($order->status->value==$s)>{{ ucfirst($s) }}</option>@endforeach</select>
                <textarea name="notes" placeholder="Notes (optional)" maxlength="{{ config('admin.limits.notes') }}" class="admin-input" rows="2"></textarea>
            </div>
            <button class="btn btn-primary w-full mt-4">Update Status</button>
        </form>
        @if(!in_array($order->status->value, ['cancelled','refunded']))
            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="form-card">
                @csrf
                <div class="form-section-title text-red-600">Cancel Order</div>
                <input name="reason" required placeholder="Cancellation reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input mb-3">
                <button class="btn btn-sm w-full bg-red-600 text-white">Cancel Order</button>
            </form>
        @endif
    </div>
</div>
@endsection
