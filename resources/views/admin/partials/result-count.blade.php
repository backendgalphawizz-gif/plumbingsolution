@props(['count' => 0, 'label' => 'records'])

<span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
    {{ number_format($count) }} {{ $label }} found
</span>
