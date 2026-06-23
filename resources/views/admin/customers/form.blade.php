@extends('admin.layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'Add Customer')
@section('page-title', $customer->exists ? 'Edit Customer' : 'Add Customer')
@section('page-subtitle', $customer->exists ? 'Update customer account details' : 'Create a new customer account')

@section('content')
<div class="form-card">
    <div class="form-section-title">{{ $customer->exists ? 'Customer Details' : 'New Customer' }}</div>
    <div class="form-section-desc">Same fields as the user app — name, mobile, email, address and profile photo.</div>

    <form action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($customer->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                @include('admin.partials.form-field', ['label' => 'Full Name', 'name' => 'name', 'limit' => 'name', 'value' => old('name', $customer->name), 'required' => true, 'placeholder' => 'e.g. John Smith'])
            </div>
            <div>
                @include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $customer->mobile), 'required' => true, 'placeholder' => '9876543210'])
            </div>
            <div>
                @include('admin.partials.form-field', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'limit' => 'email', 'value' => old('email', $customer->email), 'placeholder' => 'name@gmail.com'])
            </div>
            <div class="sm:col-span-2">
                @include('admin.partials.form-field', ['label' => 'Address', 'name' => 'address', 'type' => 'textarea', 'limit' => 'address', 'value' => old('address', $customer->address), 'rows' => 3, 'placeholder' => 'House no., street, city'])
            </div>
            <div class="sm:col-span-2 form-file-field">
                <label class="admin-label" for="field-avatar">Profile Photo</label>
                <input id="field-avatar" type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp" class="admin-input !h-auto !py-2.5">
                <p class="field-hint">JPG, PNG or WEBP · max 2 MB{{ $customer->exists ? ' · leave empty to keep current photo' : '' }}</p>
                @error('avatar')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            @if(! $customer->exists)
                <div class="sm:col-span-2">
                    @include('admin.partials.form-field', [
                        'label' => 'Password',
                        'name' => 'password',
                        'type' => 'password',
                        'limit' => 'password',
                        'required' => true,
                        'hint' => 'Minimum 8 characters (admin panel login only)',
                    ])
                </div>
            @else
                <div class="sm:col-span-2">
                    @include('admin.partials.form-field', [
                        'label' => 'Password',
                        'name' => 'password',
                        'type' => 'password',
                        'limit' => 'password',
                        'hint' => 'Leave blank to keep current password',
                    ])
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $customer->exists ? 'Update Customer' : 'Create Customer' }}</button>
        </div>
    </form>
</div>
@endsection
