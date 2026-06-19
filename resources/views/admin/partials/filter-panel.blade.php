@props(['action' => null, 'clearRoute' => null])

@php
    $clearUrl = $clearRoute ?? url()->current();
    $hasFilters = collect(request()->query())->except('page')->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
@endphp

<div class="filter-bar">
    <form method="GET" action="{{ $action ?? url()->current() }}">
        <div class="filter-bar-inner">
            {{ $slot }}
            <div class="filter-actions">
                @if($hasFilters)
                    <a href="{{ $clearUrl }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
                <button type="submit" class="btn btn-primary btn-sm">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Search
                </button>
            </div>
        </div>
    </form>
</div>
