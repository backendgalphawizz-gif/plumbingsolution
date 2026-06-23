@props(['faq' => null, 'audience'])

@php
    $faqId = data_get($faq, 'id');
    $faqQuestion = data_get($faq, 'question', '');
    $faqAnswer = data_get($faq, 'answer', '');
    $faqSortOrder = data_get($faq, 'sort_order', '');
    $faqStatus = data_get($faq, 'status', true);
    $faqFieldKey = $faqId ?: 'new';
@endphp

<input type="hidden" name="audience" value="{{ $audience }}">
<input type="hidden" name="return_tab" value="{{ $audience }}-faqs">
@if($faqId)
    <input type="hidden" name="_faq_id" value="{{ $faqId }}">
@endif

@include('admin.partials.form-field', [
    'label' => 'Question',
    'name' => 'question',
    'limit' => 'faq_question',
    'value' => old('question', $faqQuestion),
    'required' => true,
])
@include('admin.partials.form-field', [
    'label' => 'Answer',
    'name' => 'answer',
    'type' => 'textarea',
    'limit' => 'faq_answer',
    'value' => old('answer', $faqAnswer),
    'rows' => 4,
    'required' => true,
])

<div class="settings-faq-meta">
    <div class="settings-field">
        <label class="admin-label normal-case" for="faq-sort-{{ $faqFieldKey }}-{{ $audience }}">Sort Order</label>
        <input
            type="number"
            id="faq-sort-{{ $faqFieldKey }}-{{ $audience }}"
            name="sort_order"
            min="0"
            max="9999"
            value="{{ old('sort_order', $faqSortOrder) }}"
            class="admin-input"
        >
        @error('sort_order')<p class="field-error">{{ $message }}</p>@enderror
    </div>
    <label class="settings-check">
        <input
            type="checkbox"
            name="status"
            value="1"
            @checked(old('status', $faqStatus))
            class="auth-checkbox"
        >
        <span>Active</span>
    </label>
</div>
