@props(['label', 'name', 'type' => 'text', 'placeholder' => '', 'value' => null])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</label>
    @if($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20']) }}>
            {{ $slot }}
        </select>
    @else
        <input type="{{ $type }}" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value ?? request($name)) }}" placeholder="{{ $placeholder }}"
               {{ $attributes->merge(['class' => 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/20']) }}>
    @endif
</div>
