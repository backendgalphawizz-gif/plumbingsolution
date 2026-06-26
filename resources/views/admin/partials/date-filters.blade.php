@php
    $today = now()->format('Y-m-d');
    $launchDate = config('admin.launch_date');
@endphp
<div class="filter-field">
    <label class="admin-label">From Date</label>
    <input
        type="date"
        name="date_from"
        value="{{ request('date_from') }}"
        min="{{ $launchDate }}"
        max="{{ $today }}"
        data-date-min="{{ $launchDate }}"
        data-date-max="{{ $today }}"
        class="admin-input admin-date-from"
    >
    @error('date_from')<p class="field-error">{{ $message }}</p>@enderror
</div>
<div class="filter-field">
    <label class="admin-label">To Date</label>
    <input
        type="date"
        name="date_to"
        value="{{ request('date_to') }}"
        min="{{ request('date_from', $launchDate) }}"
        max="{{ $today }}"
        data-date-min="{{ $launchDate }}"
        data-date-max="{{ $today }}"
        class="admin-input admin-date-to"
    >
    @error('date_to')<p class="field-error">{{ $message }}</p>@enderror
</div>
