@extends('admin.layouts.app')
@section('title', $vendor->exists ? 'Edit Vendor' : 'Add Vendor')
@section('page-title', $vendor->exists ? 'Edit Vendor' : 'Add Vendor')
@section('page-subtitle', 'Manage vendor shop details and verification status')

@section('content')
@php $creating = ! $vendor->exists; @endphp
<div class="form-card">
    <div class="form-section-title">{{ $vendor->exists ? 'Vendor Details' : 'New Vendor' }}</div>
    <div class="form-section-desc">Same fields as the vendor app — owner, shop, location, bank and documents.</div>

    <form action="{{ $vendor->exists ? route('admin.vendors.update', $vendor) : route('admin.vendors.store') }}" method="POST" enctype="multipart/form-data" class="space-y-2">
        @csrf
        @if($vendor->exists) @method('PUT') @endif

        <div class="form-subsection">
            <h3 class="form-subsection-title">Owner Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Owner Name', 'name' => 'owner_name', 'limit' => 'name', 'value' => old('owner_name', $vendor->owner_name), 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $vendor->mobile), 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Business Mobile', 'name' => 'business_mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('business_mobile', $vendor->business_mobile), 'placeholder' => 'Optional shop contact'])</div>
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Shop Email', 'name' => 'email', 'type' => 'email', 'limit' => 'email', 'value' => old('email', $vendor->email)])</div>
            </div>
        </div>

        <div class="form-subsection">
            <h3 class="form-subsection-title">Shop Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Shop Name', 'name' => 'shop_name', 'limit' => 'shop_name', 'value' => old('shop_name', $vendor->shop_name), 'required' => true])</div>
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Address', 'name' => 'address', 'type' => 'textarea', 'limit' => 'address', 'value' => old('address', $vendor->address), 'rows' => 2, 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Country', 'name' => 'country', 'value' => old('country', $vendor->country ?? 'India'), 'required' => true, 'placeholder' => 'India'])</div>
                <div>@include('admin.partials.form-field', ['label' => 'State', 'name' => 'state', 'value' => old('state', $vendor->state), 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'City', 'name' => 'city', 'value' => old('city', $vendor->city), 'required' => true])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Pincode', 'name' => 'pincode', 'value' => old('pincode', $vendor->pincode), 'required' => true, 'inputAttributes' => ['pattern' => '[0-9]{6}', 'inputmode' => 'numeric', 'title' => '6-digit pincode']])</div>
                <div>@include('admin.partials.form-field', ['label' => 'GST Number', 'name' => 'gst_number', 'limit' => 'gst_number', 'value' => old('gst_number', $vendor->gst_number), 'placeholder' => '15-character GSTIN'])</div>
                <div>
                    <label class="admin-label">Status <span class="text-red-500">*</span></label>
                    <select name="status" required class="admin-input">@foreach(['pending','approved','rejected','suspended'] as $s)<option value="{{ $s }}" @selected(old('status', $vendor->status?->value ?? 'pending') === $s)>{{ ucfirst($s) }}</option>@endforeach</select>
                    @error('status')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2 form-file-field">
                    <label class="admin-label" for="field-shop_logo">Shop Logo @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-shop_logo" type="file" name="shop_logo" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    <p class="field-hint">JPG, PNG or WEBP · max 5 MB</p>
                    @error('shop_logo')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="form-subsection">
            <h3 class="form-subsection-title">Bank Details</h3>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">@include('admin.partials.form-field', ['label' => 'Account Holder Name', 'name' => 'account_holder_name', 'value' => old('account_holder_name', $vendor->account_holder_name), 'required' => $creating])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Account Number', 'name' => 'account_number', 'value' => old('account_number', $vendor->account_number), 'required' => $creating, 'hint' => \App\Support\AdminValidation::accountNumberHint(), 'inputAttributes' => ['inputmode' => 'numeric', 'pattern' => '[0-9]{9,18}', 'maxlength' => '18', 'data-bank-field' => 'account']])</div>
                <div>@include('admin.partials.form-field', ['label' => 'IFSC Code', 'name' => 'ifsc_code', 'value' => old('ifsc_code', $vendor->ifsc_code), 'required' => $creating, 'placeholder' => 'SBIN0001234', 'hint' => \App\Support\AdminValidation::ifscHint(), 'inputAttributes' => ['pattern' => '[A-Za-z]{4}0[A-Za-z0-9]{6}', 'maxlength' => '11', 'data-bank-field' => 'ifsc', 'style' => 'text-transform: uppercase']])</div>
                <div>@include('admin.partials.form-field', ['label' => 'Bank Name', 'name' => 'bank_name', 'value' => old('bank_name', $vendor->bank_name), 'required' => $creating])</div>
                <div>
                    <label class="admin-label">Account Type @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <select name="account_type" @if($creating) required @endif class="admin-input">
                        <option value="">Select type</option>
                        @foreach(['savings' => 'Savings', 'current' => 'Current'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('account_type', $vendor->account_type ?? 'savings') === $val)>{{ $label }}</option>
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
                    <label class="admin-label" for="field-aadhar_card">Aadhar Card @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-aadhar_card" type="file" name="aadhar_card" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    @error('aadhar_card')<p class="field-error">{{ $message }}</p>@enderror
                </div>
                <div class="form-file-field">
                    <label class="admin-label" for="field-pan_card">PAN Card @if($creating)<span class="text-red-500">*</span>@endif</label>
                    <input id="field-pan_card" type="file" name="pan_card" accept=".jpg,.jpeg,.png,.webp" @if($creating) required @endif class="admin-input !h-auto !py-2.5">
                    @error('pan_card')<p class="field-error">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5 mt-6">
            <a href="{{ route('admin.vendors.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $vendor->exists ? 'Update Vendor' : 'Create Vendor' }}</button>
        </div>
    </form>
</div>
@include('admin.partials.bank-field-scripts')
@endsection
