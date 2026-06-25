@php
    $subcategory = $subcategory ?? null;
@endphp

<div>
    @include('admin.partials.form-field', ['label' => 'Name', 'name' => 'name', 'limit' => 'category_name', 'value' => old('name', $subcategory->name ?? ''), 'required' => true])
</div>
<div>
    @include('admin.partials.form-field', ['label' => 'Slug', 'name' => 'slug', 'limit' => 'slug', 'value' => old('slug', $subcategory->slug ?? ''), 'placeholder' => 'auto-generated if empty'])
</div>
<div>
    <label class="admin-label">Image</label>
    <input type="file" name="image" accept="image/*" class="admin-input !h-auto !py-2.5">
    @if(!empty($subcategory?->image))
        <img src="{{ asset('storage/'.$subcategory->image) }}" alt="" class="mt-2 h-16 w-16 rounded-lg object-cover">
    @endif
</div>
<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="admin-label">Sort Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $subcategory->sort_order ?? 0) }}" min="0" max="9999" class="admin-input">
    </div>
    <div class="flex items-end pb-1">
        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="status" value="1" {{ old('status', $subcategory->status ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
            Active
        </label>
    </div>
</div>
