@props([
    'label',
    'name',
    'type' => 'text',
    'required' => false,
    'limit' => null,
    'max' => null,
    'hint' => null,
    'value' => null,
    'placeholder' => null,
    'rows' => 3,
    'class' => '',
    'inputAttributes' => [],
])

    @php
    $maxLength = $max ?? ($limit ? config("admin.limits.{$limit}") : null);
    $inputId = 'field-' . str_replace(['[', ']'], ['-', ''], $name);
    $hasError = $errors->has($name);
    $resolvedValue = $value ?? old($name);
    $inputAttributes = is_array($inputAttributes) ? $inputAttributes : [];
    if ($type === 'tel' && empty($inputAttributes['pattern'])) {
        $inputAttributes['pattern'] = '[6-9][0-9]{9}';
        $inputAttributes['inputmode'] = 'numeric';
        $inputAttributes['title'] = '10-digit mobile number starting with 6-9';
    }
    if (in_array($type, ['number', 'tel'], true)) {
        $class = trim($class.' admin-input-numeric');
    }
    if ($type === 'password') {
        $class = trim($class.' admin-input-password');
        if ($name === 'current_password') {
            $inputAttributes['autocomplete'] = $inputAttributes['autocomplete'] ?? 'current-password';
        } else {
            $inputAttributes['autocomplete'] = $inputAttributes['autocomplete'] ?? 'new-password';
        }
    }
@endphp

<div class="form-field {{ $hasError ? 'has-error' : '' }}">
    <div class="form-field-head">
        <label class="admin-label" for="{{ $inputId }}">
            {{ $label }}
            @if($required)<span class="text-red-500">*</span>@endif
        </label>
        @if($maxLength && $type !== 'password' && $type !== 'file')
            <span class="field-counter" data-field-counter="{{ $inputId }}" data-max="{{ $maxLength }}">
                {{ mb_strlen((string) $resolvedValue) }}/{{ $maxLength }}
            </span>
        @endif
    </div>

    @if($type === 'textarea')
        <textarea
            id="{{ $inputId }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if($required) required @endif
            @if($maxLength) maxlength="{{ $maxLength }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="admin-input {{ $class }}"
            @foreach($inputAttributes as $attrKey => $attrVal) {{ $attrKey }}="{{ $attrVal }}" @endforeach
        >{{ $resolvedValue }}</textarea>
    @elseif($type === 'password')
        <div class="admin-password-wrap">
            <input
                id="{{ $inputId }}"
                type="password"
                name="{{ $name }}"
                @if($required) required @endif
                @if($maxLength) maxlength="{{ $maxLength }}" @endif
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                class="admin-input {{ $class }}"
                @foreach($inputAttributes as $attrKey => $attrVal) {{ $attrKey }}="{{ $attrVal }}" @endforeach
            >
            @include('admin.partials.password-toggle', ['targetId' => $inputId])
        </div>
    @else
        <input
            id="{{ $inputId }}"
            type="{{ $type }}"
            name="{{ $name }}"
            value="{{ $type !== 'password' ? $resolvedValue : '' }}"
            @if($required) required @endif
            @if($maxLength) maxlength="{{ $maxLength }}" @endif
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            class="admin-input {{ $class }}"
            @foreach($inputAttributes as $attrKey => $attrVal) {{ $attrKey }}="{{ $attrVal }}" @endforeach
        >
    @endif

    @if($hint)
        <p class="field-hint">{{ $hint }}</p>
    @elseif($limit === 'faq_question')
        <p class="field-hint">Max {{ config('admin.limits.faq_question') }} characters</p>
    @elseif($limit === 'faq_answer')
        <p class="field-hint">Max {{ config('admin.limits.faq_answer') }} characters</p>
    @elseif($limit === 'name')
        <p class="field-hint">Max {{ config('admin.limits.max_name_words') }} words, {{ config('admin.limits.name') }} characters</p>
    @elseif($limit === 'email')
        <p class="field-hint">{{ \App\Support\AdminValidation::emailHint() }}</p>
    @elseif($limit === 'mobile')
        <p class="field-hint">{{ \App\Support\AdminValidation::mobileHint() }}</p>
    @endif

    @error($name)
        <p class="field-error">{{ $message }}</p>
    @enderror
</div>
