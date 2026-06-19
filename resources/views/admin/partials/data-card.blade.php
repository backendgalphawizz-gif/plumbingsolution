@props(['title', 'meta' => null])

<div class="data-card">
    <div class="data-card-header">
        <div>
            <div class="data-card-title">{{ $title }}</div>
            @if($meta)<div class="data-card-meta">{{ $meta }}</div>@endif
        </div>
        @isset($actions)<div>{{ $actions }}</div>@endisset
    </div>
    <div class="overflow-x-auto">{{ $slot }}</div>
    @isset($footer)<div class="pagination-wrap">{{ $footer }}</div>@endisset
</div>
