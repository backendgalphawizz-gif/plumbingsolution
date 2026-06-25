@php($today = now()->format('Y-m-d'))
<div class="filter-field">
    <label class="admin-label">From Date</label>
    <input type="date" name="date_from" value="{{ request('date_from') }}" max="{{ $today }}" class="admin-input admin-date-from">
    @error('date_from')<p class="field-error">{{ $message }}</p>@enderror
</div>
<div class="filter-field">
    <label class="admin-label">To Date</label>
    <input type="date" name="date_to" value="{{ request('date_to') }}" max="{{ $today }}" class="admin-input admin-date-to" @if(request('date_from')) min="{{ request('date_from') }}" @endif>
    @error('date_to')<p class="field-error">{{ $message }}</p>@enderror
</div>
