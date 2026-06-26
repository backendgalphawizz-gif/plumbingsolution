@php
    $coupon = $coupon ?? null;
    $isEdit = (bool) $coupon;
    $codeFieldId = 'field-code-'.($coupon?->id ?? 'new');
@endphp

<div>
    <div class="form-field-head">
        <label class="admin-label" for="{{ $codeFieldId }}">
            Coupon Code
            @if(! $isEdit)<span class="text-red-500">*</span>@endif
        </label>
    </div>
    <div class="flex gap-2">
        <input
            id="{{ $codeFieldId }}"
            type="text"
            name="code"
            value="{{ old('code', $coupon->code ?? '') }}"
            placeholder="Leave blank to auto-generate"
            maxlength="30"
            class="admin-input flex-1"
            style="text-transform: uppercase"
            @if($isEdit) required @endif
        >
        @if(! $isEdit)
            <button
                type="button"
                class="btn btn-secondary btn-sm shrink-0"
                data-generate-coupon-code
            >Generate</button>
        @endif
    </div>
    @if(! $isEdit)
        <p class="field-hint">Enter a custom code or leave blank and click Generate.</p>
    @endif
    @error('code')<p class="field-error">{{ $message }}</p>@enderror
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
            'hint' => 'Optional. Cannot be a past date.',
            'inputAttributes' => [
                'min' => now()->format('Y-m-d'),
                'data-date-min' => now()->format('Y-m-d'),
                'class' => 'admin-input admin-date-expiry',
            ],
        ])
    </div>
</div>

<div class="flex items-end pb-1">
    <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
        <input type="checkbox" name="status" value="1" {{ old('status', $coupon->status ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
        Active
    </label>
</div>

@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        const randomCode = () => Array.from({ length: 8 }, () => chars[Math.floor(Math.random() * chars.length)]).join('');

        document.querySelectorAll('[data-generate-coupon-code]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = button.closest('form');
                const input = form?.querySelector('input[name="code"]');
                if (input) {
                    input.value = randomCode();
                }
            });
        });
    });
    </script>
    @endpush
@endonce
