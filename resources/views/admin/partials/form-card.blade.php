@props(['back'])

<form {{ $attributes->merge(['class' => 'mx-auto max-w-3xl space-y-6']) }}>
    @csrf
    {{ $slot }}
    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
        <a href="{{ $back }}" class="rounded-lg border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">{{ $submitLabel ?? 'Save' }}</button>
    </div>
</form>
