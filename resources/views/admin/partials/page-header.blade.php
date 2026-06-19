@props(['title', 'description' => null])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        @if(isset($title))
            <h2 class="text-lg font-semibold text-slate-800">{{ $title }}</h2>
        @endif
        @if($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
        @if(isset($stats))
            <div class="mt-3 flex flex-wrap gap-2">{{ $stats }}</div>
        @endif
    </div>
    @if(isset($actions))
        <div class="flex shrink-0 flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
