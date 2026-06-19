@extends('admin.layouts.app')
@section('title', $vendor->exists ? 'Edit Vendor' : 'Add Vendor')
@section('page-title', $vendor->exists ? 'Edit Vendor' : 'Add Vendor')
@section('page-subtitle', 'Manage vendor shop details and verification status')

@section('content')
<div class="form-card mx-auto max-w-2xl">
    <div class="form-section-title">{{ $vendor->exists ? 'Vendor Details' : 'New Vendor' }}</div>
    <div class="form-section-desc">Shop information, GST details and verification documents.</div>

    <form action="{{ $vendor->exists ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($vendor->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Shop Name', 'name' => 'shop_name', 'limit' => 'shop_name', 'value' => old('shop_name', $vendor->shop_name), 'required' => true])</div>
            <div>@include('admin.partials.form-field', ['label' => 'Owner Name', 'name' => 'owner_name', 'limit' => 'name', 'value' => old('owner_name', $vendor->owner_name), 'required' => true])</div>
            <div>@include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $vendor->mobile), 'required' => true])</div>
            <div>@include('admin.partials.form-field', ['label' => 'GST Number', 'name' => 'gst_number', 'limit' => 'gst_number', 'value' => old('gst_number', $vendor->gst_number), 'placeholder' => '15-character GSTIN', 'hint' => 'Max 15 characters'])</div>
            <div>
                <label class="admin-label">Status *</label>
                <select name="status" required class="admin-input">@foreach(['pending','approved','rejected','suspended'] as $s)<option value="{{ $s }}" @selected(old('status', $vendor->status?->value ?? 'pending') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Address', 'name' => 'address', 'type' => 'textarea', 'limit' => 'address', 'value' => old('address', $vendor->address), 'rows' => 2])</div>
            <div><label class="admin-label">GST Certificate</label><input type="file" name="gst_document" accept=".pdf,.jpg,.jpeg,.png" class="admin-input !h-auto !py-2.5"></div>
            <div><label class="admin-label">Shop License</label><input type="file" name="license_document" accept=".pdf,.jpg,.jpeg,.png" class="admin-input !h-auto !py-2.5"></div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $vendor->exists ? 'Update Vendor' : 'Create Vendor' }}</button>
        </div>
    </form>
</div>
@endsection
