@extends('admin.layouts.app')
@section('title', $bulkOrder->reference_number)
@section('page-title', 'Bulk Order Details')
@section('page-subtitle', 'Review files, build quotation and send to customer')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="form-card">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-bold text-slate-900">{{ $bulkOrder->reference_number }}</h2>
            @include('admin.partials.status-badge', ['status' => $bulkOrder->status])
        </div>

        <dl class="mb-5 grid gap-3 text-sm sm:grid-cols-2">
            <div><dt class="admin-label">Customer</dt><dd class="mt-1">{{ $bulkOrder->user?->name ?? '—' }}</dd></div>
            <div><dt class="admin-label">Contact</dt><dd class="mt-1">{{ $bulkOrder->full_name }} · {{ $bulkOrder->mobile }}</dd></div>
            <div class="sm:col-span-2"><dt class="admin-label">Requirement</dt><dd class="mt-1 text-slate-600">{{ $bulkOrder->requirement_description ?? 'No description provided.' }}</dd></div>
        </dl>

        <h3 class="detail-panel-title !mb-3 !pb-0 !border-0">Uploaded Files</h3>
        @forelse($bulkOrder->files as $file)
            <div class="mb-2 flex items-center justify-between gap-3 rounded-lg border border-slate-200 p-3 text-sm">
                <div class="min-w-0">
                    <p class="truncate font-medium text-slate-800">{{ $file->original_name ?? basename($file->file_path) }}</p>
                    <p class="text-xs uppercase text-slate-400">{{ $file->file_type }}</p>
                </div>
                <a href="{{ asset('storage/'.$file->file_path) }}" target="_blank" class="action-btn shrink-0">View File</a>
            </div>
        @empty
            <p class="text-sm text-slate-400">No files uploaded.</p>
        @endforelse

        <form action="{{ route('admin.bulk-orders.review', $bulkOrder) }}" method="POST" class="mt-5 border-t border-slate-100 pt-5">
            @csrf
            <label class="admin-label">Admin Notes</label>
            <textarea name="admin_notes" placeholder="Review notes after checking the files..." maxlength="{{ config('admin.limits.notes') }}" class="admin-input mb-3" rows="3">{{ old('admin_notes', $bulkOrder->admin_notes) }}</textarea>
            <button type="submit" class="btn btn-secondary btn-sm">Save Review</button>
        </form>
    </div>

    <div class="form-card">
        <div class="form-section-title">Quotations</div>
        <div class="form-section-desc">Add product line items and send the quotation directly to the customer.</div>

        @forelse($bulkOrder->quotations->sortByDesc('id') as $quotation)
            @php
                $items = is_array($quotation->details) ? ($quotation->details['items'] ?? []) : [];
                $notes = is_array($quotation->details) ? ($quotation->details['notes'] ?? null) : null;
            @endphp
            <div class="mb-4 rounded-xl border border-slate-200 p-4 text-sm">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <p class="font-semibold text-slate-800">{{ $quotation->quotation_number }}</p>
                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold uppercase text-slate-600">{{ $quotation->status }}</span>
                </div>

                @if($items !== [])
                    <table class="admin-table mb-3">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>₹{{ number_format($item['price'], 2) }}</td>
                                    <td>{{ $item['quantity'] }}</td>
                                    <td class="font-semibold">₹{{ number_format($item['total'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <p class="text-base font-bold text-emerald-700">Grand Total: ₹{{ number_format($quotation->amount, 2) }}</p>
                @if($quotation->valid_until)
                    <p class="mt-2 text-sm text-slate-600">Valid until: <span class="font-semibold">{{ $quotation->valid_until->format('M d, Y') }}</span></p>
                @endif
                @if($notes)<p class="mt-2 text-slate-500">{{ $notes }}</p>@endif

                @if($quotation->status === 'draft')
                    <form action="{{ route('admin.bulk-orders.quotations.send', [$bulkOrder, $quotation]) }}" method="POST" class="mt-3">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send this quotation to the customer?')">Send to Customer</button>
                    </form>
                @elseif($quotation->status === 'sent')
                    <p class="mt-2 text-xs text-slate-400">Sent {{ $quotation->sent_at?->format('M d, Y • g:i A') }}</p>
                @elseif($quotation->status === 'expired')
                    <p class="mt-2 text-xs text-amber-600">Expired on {{ $quotation->valid_until?->format('M d, Y') }}</p>
                @elseif($quotation->status === 'rejected')
                    <p class="mt-2 text-xs text-red-600">Rejected: {{ $quotation->rejection_reason }}</p>
                @elseif($quotation->status === 'approved')
                    <p class="mt-2 text-xs text-emerald-600">Approved by customer {{ $quotation->responded_at?->format('M d, Y • g:i A') }}</p>
                @endif
            </div>
        @empty
            <p class="mb-4 text-sm text-slate-400">No quotations yet.</p>
        @endforelse

        @if($bulkOrder->canReceiveQuotation())
        <form action="{{ route('admin.bulk-orders.quotations.store', $bulkOrder) }}" method="POST" class="border-t border-slate-100 pt-5" id="quotation-form">
            @csrf
            <h3 class="admin-label mb-3">Send Quotation to Customer</h3>

            <div class="overflow-x-auto">
                <table class="admin-table" id="quotation-items-table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th class="w-28">Price (₹)</th>
                            <th class="w-24">Qty</th>
                            <th class="w-28">Total (₹)</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="quotation-items-body">
                        <tr class="quotation-row">
                            <td><input type="text" name="items[0][product_name]" required placeholder="Product name" class="admin-input" maxlength="255"></td>
                            <td><input type="number" name="items[0][price]" required min="0" step="0.01" class="admin-input item-price" placeholder="0.00"></td>
                            <td><input type="number" name="items[0][quantity]" required min="1" value="1" class="admin-input item-qty"></td>
                            <td><input type="text" readonly class="admin-input item-total bg-slate-50" value="0.00"></td>
                            <td><button type="button" class="action-btn danger remove-row" disabled>×</button></td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-right font-semibold text-slate-700">Grand Total</td>
                            <td colspan="2" class="font-bold text-emerald-700" id="quotation-grand-total">₹0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <button type="button" id="add-quotation-row" class="btn btn-secondary btn-sm mt-3">+ Add Product</button>

            <div class="mt-4">
                <label class="admin-label">Valid Until <span class="text-red-500">*</span></label>
                <input type="date" name="valid_until" value="{{ old('valid_until') }}" required min="{{ now()->format('Y-m-d') }}" class="admin-input">
                <p class="field-hint mt-1">Customer can accept the quotation until this date. It expires automatically after.</p>
                @error('valid_until')<p class="field-error">{{ $message }}</p>@enderror
            </div>

            <div class="mt-4">
                <label class="admin-label">Quotation Notes (optional)</label>
                <textarea name="notes" placeholder="Delivery timeline, terms, etc." maxlength="1000" class="admin-input" rows="2">{{ old('notes') }}</textarea>
            </div>

            @if($errors->any())
                <div class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif

            <button type="submit" class="btn btn-primary btn-sm mt-4" onclick="return confirm('Send this quotation to the customer?')">Send to Customer</button>
        </form>
        @else
            @if($bulkOrder->quotations->isNotEmpty())
                <p class="border-t border-slate-100 pt-5 text-sm text-slate-500">Quotation already sent. A new quotation can be created only if the customer rejects or the previous one expires.</p>
            @endif
        @endif
    </div>
</div>

@if($bulkOrder->canReceiveQuotation())
@push('scripts')
<script>
(function () {
    const body = document.getElementById('quotation-items-body');
    const addBtn = document.getElementById('add-quotation-row');
    const grandTotalEl = document.getElementById('quotation-grand-total');
    let rowIndex = body.querySelectorAll('.quotation-row').length;

    function formatMoney(value) {
        return '₹' + Number(value).toFixed(2);
    }

    function recalcRow(row) {
        const price = parseFloat(row.querySelector('.item-price')?.value) || 0;
        const qty = parseInt(row.querySelector('.item-qty')?.value, 10) || 0;
        const total = price * qty;
        row.querySelector('.item-total').value = total.toFixed(2);
        return total;
    }

    function recalcGrandTotal() {
        let grand = 0;
        body.querySelectorAll('.quotation-row').forEach((row) => {
            grand += recalcRow(row);
        });
        grandTotalEl.textContent = formatMoney(grand);
    }

    function bindRow(row) {
        row.querySelectorAll('.item-price, .item-qty').forEach((input) => {
            input.addEventListener('input', recalcGrandTotal);
        });
        row.querySelector('.remove-row')?.addEventListener('click', () => {
            if (body.querySelectorAll('.quotation-row').length <= 1) return;
            row.remove();
            reindexRows();
            recalcGrandTotal();
        });
    }

    function reindexRows() {
        body.querySelectorAll('.quotation-row').forEach((row, index) => {
            row.querySelector('[name*="[product_name]"]').name = `items[${index}][product_name]`;
            row.querySelector('[name*="[price]"]').name = `items[${index}][price]`;
            row.querySelector('[name*="[quantity]"]').name = `items[${index}][quantity]`;
            const removeBtn = row.querySelector('.remove-row');
            removeBtn.disabled = body.querySelectorAll('.quotation-row').length <= 1;
        });
        rowIndex = body.querySelectorAll('.quotation-row').length;
    }

    addBtn.addEventListener('click', () => {
        const row = document.createElement('tr');
        row.className = 'quotation-row';
        row.innerHTML = `
            <td><input type="text" name="items[${rowIndex}][product_name]" required placeholder="Product name" class="admin-input" maxlength="255"></td>
            <td><input type="number" name="items[${rowIndex}][price]" required min="0" step="0.01" class="admin-input item-price" placeholder="0.00"></td>
            <td><input type="number" name="items[${rowIndex}][quantity]" required min="1" value="1" class="admin-input item-qty"></td>
            <td><input type="text" readonly class="admin-input item-total bg-slate-50" value="0.00"></td>
            <td><button type="button" class="action-btn danger remove-row">×</button></td>
        `;
        body.appendChild(row);
        rowIndex++;
        bindRow(row);
        reindexRows();
        recalcGrandTotal();
    });

    body.querySelectorAll('.quotation-row').forEach(bindRow);
    recalcGrandTotal();
})();
</script>
@endpush
@endif
@endsection
