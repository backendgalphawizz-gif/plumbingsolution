<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }} @if($required ?? false)<span class="text-red-500">*</span>@endif</label>
    {{ $slot }}
    @error($name ?? '')
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
