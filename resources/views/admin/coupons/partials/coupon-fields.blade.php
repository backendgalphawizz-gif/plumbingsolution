@php
    $coupon = $coupon ?? null;
@endphp

<div>
    @include('admin.partials.form-field', [
        'label' => 'Coupon Code',
        'name' => 'code',
        'max' => 30,
        'value' => old('code', $coupon->code ?? ''),
        'placeholder' => 'e.g. WELCOME10',
        'required' => true,
        'inputAttributes' => ['style' => 'text-transform: uppercase'],
    ])
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="admin-label">Discount Type <span class="text-red-500">*</span></label>
        <select name="discount_type" class="admin-input" required>
            <option value="percent" @selected(old('discount_type', $coupon->discount_type ?? 'percent') === 'percent')>Percentage (%)</option>
            <option value="fixed" @selected(old('discount_type', $coupon->discount_type ?? '') === 'fixed')>Fixed Amount (₹)</option>
        </select>
        @error('discount_type')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <div>
        @include('admin.partials.form-field', [
            'label' => 'Discount Value',
            'name' => 'discount_value',
            'type' => 'number',
            'value' => old('discount_value', $coupon->discount_value ?? ''),
            'required' => true,
            'inputAttributes' => ['min' => '0.01', 'step' => '0.01'],
        ])
    </div>
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        @include('admin.partials.form-field', [
            'label' => 'Minimum Order Amount (₹)',
            'name' => 'min_order_amount',
            'type' => 'number',
            'value' => old('min_order_amount', $coupon->min_order_amount ?? 0),
            'inputAttributes' => ['min' => '0', 'step' => '0.01'],
            'hint' => 'Leave 0 for no minimum.',
        ])
    </div>
    <div>
        @include('admin.partials.form-field', [
            'label' => 'Expiry Date',
            'name' => 'expires_at',
            'type' => 'date',
            'value' => old('expires_at', isset($coupon->expires_at) ? $coupon->expires_at->format('Y-m-d') : ''),
            'hint' => 'Optional. Leave empty for no expiry.',
        ])
    </div>
</div>

<div class="flex items-end pb-1">
    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
        <input type="checkbox" name="status" value="1" {{ old('status', $coupon->status ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
        Active
    </label>
</div>
