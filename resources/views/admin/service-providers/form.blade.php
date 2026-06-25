@extends('admin.layouts.app')
@section('title', $serviceProvider->exists ? 'Edit Provider' : 'Add Provider')
@section('page-title', $serviceProvider->exists ? 'Edit Service Provider' : 'Add Service Provider')
@section('page-subtitle', 'Manage plumber / technician profiles and skills')

@section('content')
@php
    $creating = ! $serviceProvider->exists;
    $userEmail = old('email', $serviceProvider->user?->email);
@endphp
<div class="form-card">
    <div class="form-section-title">{{ $serviceProvider->exists ? 'Provider Details' : 'New Provider' }}</div>
    <div class="form-section-desc">Same fields as the provider app — personal, skills, bank and verification documents.</div>

    <form action="{{ $serviceProvider->exists ? route('admin.service-providers.update', $serviceProvider) : route('admin.service-providers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-2">
        @csrf
        @if($serviceProvider->exists) @method('PUT') @endif

        <div class="form-subsection">
            <h3 class="form-subsection-title">Personal Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>@include('admin.partials.form-field', ['label' => 'Full Name', 'name' => 'name', 'limit' => 'name', 'value' => old('name', $serviceProvider->name), 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $serviceProvider->mobile), 'required' => true])</div>
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'limit' => 'email', 'value' => $userEmail, 'hint' => $serviceProvider->user_id ? 'Synced with linked user account' : 'Optional — used when a user account is linked'])</div>
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Address / Service Area', 'name' => 'address', 'type' => 'textarea', 'limit' => 'service_area', 'value' => old('address', $serviceProvider->service_area), 'rows' => 2, 'required' => true])</div>
                <div class="sm:col-span-2 form-file-field">
                    <label class="admin-label" for="field-avatar">Profile Photo</label>
                    <input id="field-avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="admin-input !h-auto !py-2.5">
                    <p class="field-hint">JPG, PNG or WEBP · max 20 MB</p>
                    @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="form-subsection">
            <h3 class="form-subsection-title">Professional Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Skills', 'name' => 'skills', 'limit' => 'skills', 'value' => old('skills', is_array($serviceProvider->skills) ? implode(', ', $serviceProvider->skills) : ''), 'required' => $creating, 'placeholder' => 'Leak Repair, Pipe Fitting', 'hint' => 'Separate with commas · at least one skill required'])</div>
                <div>
                    <label class="admin-label">Experience (years) <span class="text-red-500">*</span></label>
                    <input type="number" name="experience_years" min="0" max="50" value="{{ old('experience_years', $serviceProvider->experience_years ?? 0) }}" required class="admin-input">
                    @error('experience_years')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="admin-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="admin-input">@foreach(['pending','approved','rejected','suspended'] as $s)<option value="{{ $s }}" @selected(old('status', $serviceProvider->status?->value ?? 'pending') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="form-subsection">
            <h3 class="form-subsection-title">Bank Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Account Holder Name', 'name' => 'account_holder_name', 'value' => old('account_holder_name', $serviceProvider->account_holder_name), 'required' => $creating])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Account Number', 'name' => 'account_number', 'value' => old('account_number', $serviceProvider->account_number), 'required' => $creating, 'inputAttributes' => ['inputmode' => 'numeric']])</div>
                <div>@include('admin.partials.form-field', ['label' => 'IFSC Code', 'name' => 'ifsc_code', 'value' => old('ifsc_code', $serviceProvider->ifsc_code), 'required' => $creating, 'placeholder' => 'SBIN0001234', 'inputAttributes' => ['pattern' => '[A-Za-z]{4}0[A-Za-z0-9]{6}', 'title' => 'Valid 11-character IFSC code']])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Bank Name', 'name' => 'bank_name', 'value' => old('bank_name', $serviceProvider->bank_name), 'required' => $creating])</div>
                <div>
                    <label class="admin-label">Account Type @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <select name="account_type" @if($creating) required @endif class="admin-input">
                        <option value="">Select type</option>
                        @foreach(['savings' => 'Savings', 'current' => 'Current'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('account_type', $serviceProvider->account_type ?? 'savings') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('account_type')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="form-subsection">
            <h3 class="form-subsection-title">Documents</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="form-file-field">
                    <label class="admin-label" for="field-aadhar_front">Aadhar Front @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-aadhar_front" type="file" name="aadhar_front" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    @error('aadhar_front')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-file-field">
                    <label class="admin-label" for="field-aadhar_back">Aadhar Back @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-aadhar_back" type="file" name="aadhar_back" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    @error('aadhar_back')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-file-field">
                    <label class="admin-label" for="field-pan_card">PAN Card @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-pan_card" type="file" name="pan_card" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    @error('pan_card')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-file-field sm:col-span-2">
                    <label class="admin-label" for="field-gallery">Work Gallery Images</label>
                    <input id="field-gallery" type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp" multiple class="admin-input !h-auto !py-2.5">
                    <p class="field-hint">Optional portfolio images</p>
                    @error('gallery_images')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5 mt-6">
            <a href="{{ route('admin.service-providers.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $serviceProvider->exists ? 'Update Provider' : 'Create Provider' }}</button>
        </div>
    </form>
</div>
@endsection
