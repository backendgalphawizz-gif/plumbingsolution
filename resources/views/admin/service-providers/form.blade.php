@extends('admin.layouts.app')
@section('title', $serviceProvider->exists ? 'Edit Provider' : 'Add Provider')
@section('page-title', $serviceProvider->exists ? 'Edit Service Provider' : 'Add Service Provider')
@section('page-subtitle', 'Manage plumber / technician profiles and skills')

@section('content')
<div class="form-card mx-auto max-w-2xl">
    <div class="form-section-title">{{ $serviceProvider->exists ? 'Provider Details' : 'New Provider' }}</div>
    <div class="form-section-desc">Profile, skills, service area and ID verification.</div>

    <form action="{{ $serviceProvider->exists ? route('admin.service-providers.update', $serviceProvider) : route('admin.service-providers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($serviceProvider->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div>@include('admin.partials.form-field', ['label' => 'Full Name', 'name' => 'name', 'limit' => 'name', 'value' => old('name', $serviceProvider->name), 'required' => true])</div>
            <div>@include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $serviceProvider->mobile), 'required' => true])</div>
            <div>
                <label class="admin-label">Experience (years) *</label>
                <input type="number" name="experience_years" min="0" max="50" value="{{ old('experience_years', $serviceProvider->experience_years ?? 0) }}" required class="admin-input">
                @error('experience_years')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Status *</label>
                <select name="status" required class="admin-input">@foreach(['pending','approved','rejected','suspended'] as $s)<option value="{{ $s }}" @selected(old('status', $serviceProvider->status?->value ?? 'pending') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Skills', 'name' => 'skills', 'limit' => 'skills', 'value' => old('skills', is_array($serviceProvider->skills) ? implode(', ', $serviceProvider->skills) : ''), 'placeholder' => 'Leak Repair, Pipe Fitting...', 'hint' => 'Separate with commas · max '.config('admin.limits.skills').' characters'])</div>
            <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Service Area', 'name' => 'service_area', 'type' => 'textarea', 'limit' => 'service_area', 'value' => old('service_area', $serviceProvider->service_area), 'rows' => 2])</div>
            <div class="sm:col-span-2"><label class="admin-label">Profile Photo</label><input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="admin-input !h-auto !py-2.5"></div>
            <div class="sm:col-span-2"><label class="admin-label">Work Gallery Images</label><input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple class="admin-input !h-auto !py-2.5"></div>
            <div class="sm:col-span-2"><label class="admin-label">ID Proof Document</label><input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" class="admin-input !h-auto !py-2.5"></div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.service-providers.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $serviceProvider->exists ? 'Update Provider' : 'Create Provider' }}</button>
        </div>
    </form>
</div>
@endsection
