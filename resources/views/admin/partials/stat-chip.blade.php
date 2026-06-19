@props(['url', 'active' => false, 'color' => 'slate'])

<a href="{{ $url }}" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium transition {{ $active ? 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-200' : 'bg-'.$color.'-50 text-'.$color.'-700 hover:bg-emerald-50 hover:text-emerald-700' }}">
    {{ $slot }}
</a>
