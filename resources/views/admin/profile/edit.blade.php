@extends('admin.layouts.app')
@section('title', 'Profile')
@section('page-title', 'Profile Management')
@section('page-subtitle', 'Update your account details and password')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <form action="{{ route('admin.profile.update') }}" method="POST" class="form-card">@csrf @method('PUT')
        <div class="form-section-title">Profile Information</div>
        <div class="form-section-desc">Your display name and contact details.</div>
        <div class="space-y-4">
            @include('admin.partials.form-field', ['label' => 'Name', 'name' => 'name', 'limit' => 'name', 'value' => old('name', $admin->name), 'required' => true])
            <div>
                <label class="admin-label">Email</label>
                <input value="{{ $admin->email }}" disabled class="admin-input bg-slate-50">
            </div>
            @include('admin.partials.form-field', ['label' => 'Mobile', 'name' => 'mobile', 'type' => 'tel', 'limit' => 'mobile', 'value' => old('mobile', $admin->mobile)])
            @include('admin.partials.form-field', ['label' => 'Role Title', 'name' => 'role_title', 'limit' => 'role_title', 'value' => old('role_title', $admin->role_title), 'placeholder' => 'e.g. Senior Admin'])
        </div>
        <button class="btn btn-primary mt-5">Update Profile</button>
    </form>

    <form action="{{ route('admin.profile.password') }}" method="POST" class="form-card">@csrf @method('PUT')
        <div class="form-section-title">Change Password</div>
        <div class="form-section-desc">Use a strong password with at least 8 characters.</div>
        <div class="space-y-4">
            @include('admin.partials.form-field', ['label' => 'Current Password', 'name' => 'current_password', 'type' => 'password', 'limit' => 'password', 'required' => true])
            @include('admin.partials.form-field', ['label' => 'New Password', 'name' => 'password', 'type' => 'password', 'limit' => 'password', 'required' => true])
            @include('admin.partials.form-field', ['label' => 'Confirm Password', 'name' => 'password_confirmation', 'type' => 'password', 'limit' => 'password', 'required' => true])
        </div>
        <button class="btn btn-secondary mt-5">Change Password</button>
    </form>
</div>
@endsection
