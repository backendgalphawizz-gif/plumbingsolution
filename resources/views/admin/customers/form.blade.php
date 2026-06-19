@extends('admin.layouts.app')
@section('title', $customer->exists ? 'Edit Customer' : 'Add Customer')
@section('page-title', $customer->exists ? 'Edit Customer' : 'Add Customer')
@section('page-subtitle', $customer->exists ? 'Update customer account details' : 'Create a new customer account')

@section('content')
<div class="form-card mx-auto max-w-2xl">
    <div class="form-section-title">{{ $customer->exists ? 'Customer Details' : 'New Customer' }}</div>
    <div class="form-section-desc">Fill in the account information below.</div>

    <form action="{{ $customer->exists ? route('admin.customers.update', $customer) : route('admin.customers.store') }}" method="POST" class="space-y-5">
        @csrf
        @if($customer->exists) @method('PUT') @endif

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                @include('admin.partials.form-field', ['label' => 'Full Name', 'name' => 'name', 'limit' => 'name', 'value' => old('name', $customer->name), 'required' => true, 'placeholder' => 'e.g. John Smith'])
            </div>
            <div>
                @include('admin.partials.form-field', ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'limit' => 'email', 'value' => old('email', $customer->email), 'required' => true])
            </div>
            <div>
                @include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $customer->mobile), 'placeholder' => '9876543210'])
            </div>
            <div class="sm:col-span-2">
                @include('admin.partials.form-field', [
                    'label' => 'Password'.($customer->exists ? '' : ''),
                    'name' => 'password',
                    'type' => 'password',
                    'limit' => 'password',
                    'required' => ! $customer->exists,
                    'hint' => $customer->exists ? 'Leave blank to keep current password' : 'Minimum 8 characters',
                ])
            </div>
            <div class="sm:col-span-2">
                @include('admin.partials.form-field', ['label' => 'Address', 'name' => 'address', 'type' => 'textarea', 'limit' => 'address', 'value' => old('address', $customer->address), 'rows' => 3])
            </div>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $customer->exists ? 'Update Customer' : 'Create Customer' }}</button>
        </div>
    </form>
</div>
@endsection
