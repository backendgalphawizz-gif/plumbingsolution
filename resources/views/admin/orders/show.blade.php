@extends('admin.layouts.app')
@section('title', $order->order_number)
@section('page-title', 'Order Details')
@section('page-subtitle', 'Full order summary, customer & vendor info, and delivery progress')

@section('content')
@php
    $orderSteps = [
        ['key' => 'pending', 'label' => 'Pending'],
        ['key' => 'accepted', 'label' => 'Accepted'],
        ['key' => 'packed', 'label' => 'Packed'],
        ['key' => 'shipped', 'label' => 'Shipped'],
        ['key' => 'delivered', 'label' => 'Delivered'],
    ];
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <div class="detail-header !mb-0">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">{{ $order->order_number }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Placed {{ $order->created_at->format('M d, Y • g:i A') }}</p>
                </div>
                @include('admin.partials.status-badge', ['status' => $order->status])
            </div>
        </div>

        <div class="detail-panel">
            <h3 class="detail-panel-title">Order Progress</h3>
            @include('admin.partials.status-timeline', [
                'steps' => $orderSteps,
                'current' => $order->status,
                'terminal' => ['cancelled', 'returned', 'refunded'],
                'entityLabel' => 'order',
                'logs' => $order->statusLogs,
            ])
        </div>

        <div class="detail-panel">
            <h3 class="detail-panel-title">Order Items</h3>
            <div class="overflow-x-auto">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td class="font-medium">{{ $item->product_name }}</td>
                                <td class="text-slate-500">{{ $item->sku ?? '—' }}</td>
                                <td>₹{{ number_format($item->unit_price ?? ($item->total_price / max($item->quantity, 1)), 2) }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td class="font-semibold">₹{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-4">
                <div class="amount-row"><span>Subtotal</span><span>₹{{ number_format($order->subtotal ?? $order->total_amount, 2) }}</span></div>
                @if($order->discount_amount > 0)
                    <div class="amount-row"><span>Discount</span><span class="text-emerald-600">− ₹{{ number_format($order->discount_amount, 2) }}</span></div>
                @endif
                @if($order->tax_amount > 0)
                    <div class="amount-row"><span>Tax</span><span>₹{{ number_format($order->tax_amount, 2) }}</span></div>
                @endif
                @if($order->shipping_amount > 0)
                    <div class="amount-row"><span>Shipping</span><span>₹{{ number_format($order->shipping_amount, 2) }}</span></div>
                @endif
                <div class="amount-row total"><span>Grand Total</span><span class="text-emerald-700">₹{{ number_format($order->total_amount, 2) }}</span></div>
            </div>
        </div>

        <div class="detail-grid !grid-cols-1 lg:!grid-cols-2">
            <div class="detail-panel">
                <h3 class="detail-panel-title">Shipping Address</h3>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $order->shipping_address ?? '—' }}</p>
            </div>
            <div class="detail-panel">
                <h3 class="detail-panel-title">Billing Address</h3>
                <p class="text-sm text-slate-600 whitespace-pre-line">{{ $order->billing_address ?? $order->shipping_address ?? '—' }}</p>
            </div>
        </div>

        @if($order->tracking_number || $order->courier_name)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Tracking</h3>
                <dl class="detail-dl">
                    <div><dt class="admin-label">Courier</dt><dd class="mt-1">{{ $order->courier_name ?? '—' }}</dd></div>
                    <div><dt class="admin-label">Tracking #</dt><dd class="mt-1 font-mono text-sm">{{ $order->tracking_number ?? '—' }}</dd></div>
                </dl>
            </div>
        @endif

        @if($order->status->value === 'cancelled' && $order->cancellation_reason)
            <div class="detail-panel border-red-200 bg-red-50/50">
                <h3 class="detail-panel-title text-red-700">Cancellation</h3>
                <p class="text-sm text-red-800">{{ $order->cancellation_reason }}</p>
                @if($order->cancelled_at)
                    <p class="mt-2 text-xs text-red-600">Cancelled {{ $order->cancelled_at->format('M d, Y • g:i A') }}</p>
                @endif
            </div>
        @endif

        @if($order->notes)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Order Notes</h3>
                <p class="text-sm text-slate-600">{{ $order->notes }}</p>
            </div>
        @endif
    </div>

    <div class="space-y-4">
        @include('admin.partials.entity-user-card', [
            'user' => $order->user,
            'ordersCount' => $order->user?->orders_count,
            'bookingsCount' => $order->user?->service_bookings_count,
        ])

        @if($order->vendor)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Vendor / Shop</h3>
                <dl class="detail-dl">
                    <div class="span-full"><dt class="admin-label">Shop</dt><dd class="mt-1 font-semibold">{{ $order->vendor->shop_name }}</dd></div>
                    <div><dt class="admin-label">Owner</dt><dd class="mt-1">{{ $order->vendor->owner_name }}</dd></div>
                    <div><dt class="admin-label">Mobile</dt><dd class="mt-1">{{ $order->vendor->mobile }}</dd></div>
                    @if($order->vendor->gst_number)
                        <div><dt class="admin-label">GST</dt><dd class="mt-1">{{ $order->vendor->gst_number }}</dd></div>
                    @endif
                </dl>
                <a href="{{ route('admin.vendors.show', $order->vendor) }}" class="action-btn mt-4 inline-flex">View Vendor</a>
            </div>
        @endif

        @if($order->payment)
            <div class="detail-panel">
                <h3 class="detail-panel-title">Payment</h3>
                <dl class="detail-dl">
                    <div><dt class="admin-label">Method</dt><dd class="mt-1 capitalize">{{ $order->payment->method->value ?? $order->payment->method }}</dd></div>
                    <div><dt class="admin-label">Status</dt><dd class="mt-1">@include('admin.partials.status-badge', ['status' => $order->payment->status->value ?? $order->payment->status])</dd></div>
                    <div><dt class="admin-label">Amount</dt><dd class="mt-1 font-semibold text-emerald-700">₹{{ number_format($order->payment->amount, 2) }}</dd></div>
                    @if($order->payment->gateway_payment_id)
                        <div class="span-full"><dt class="admin-label">Transaction ID</dt><dd class="mt-1 font-mono text-xs break-all">{{ $order->payment->gateway_payment_id }}</dd></div>
                    @endif
                </dl>
            </div>
        @endif

        <form action="{{ route('admin.orders.update-status', $order) }}" method="POST" class="form-card">@csrf @method('PUT')
            <div class="form-section-title">Update Status</div>
            <div class="space-y-3">
                <select name="status" class="admin-input">
                    @foreach(['pending','accepted','packed','shipped','delivered','cancelled','returned','refunded'] as $s)
                        <option value="{{ $s }}" @selected($order->status->value==$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <textarea name="notes" placeholder="Notes for status history (optional)" maxlength="{{ config('admin.limits.notes') }}" class="admin-input" rows="2"></textarea>
            </div>
            <button class="btn btn-primary w-full mt-4">Update Status</button>
        </form>

        @if(!in_array($order->status->value, ['cancelled','refunded']))
            <form action="{{ route('admin.orders.cancel', $order) }}" method="POST" class="form-card">
                @csrf
                <div class="form-section-title text-red-600">Cancel Order</div>
                <input name="reason" required placeholder="Cancellation reason" maxlength="{{ config('admin.limits.reason') }}" class="admin-input mb-3">
                <button class="btn btn-sm w-full bg-red-600 text-white" onclick="return confirm('Cancel this order?')">Cancel Order</button>
            </form>
        @endif
    </div>
</div>
@endsection
