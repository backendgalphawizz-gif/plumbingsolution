@extends('admin.layouts.app')
@section('title', $service->exists ? 'Edit Service' : 'Add Service')
@section('page-title', $service->exists ? 'Edit Service' : 'Add Service')
@section('page-subtitle', 'Platform catalog or provider-linked service')

@section('content')
<div class="page-toolbar">
    <a href="{{ route('admin.services.index') }}" class="btn btn-secondary btn-sm">Back to list</a>
</div>

<div class="form-card mx-auto max-w-3xl">
    <div class="form-section-title">{{ $service->exists ? 'Service Details' : 'New Service' }}</div>
    <div class="form-section-desc">Set category, pricing and optional provider link.</div>

    <form action="{{ $service->exists ? route('admin.services.update', $service) : route('admin.services.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @if($service->exists) @method('PUT') @endif

        <div class="grid gap-5 md:grid-cols-2">
            <div class="md:col-span-2">
                @include('admin.partials.form-field', [
                    'label' => 'Service Name',
                    'name' => 'name',
                    'limit' => 'product_name',
                    'value' => old('name', $service->name),
                    'required' => true,
                ])
            </div>
            <div>
                <label class="admin-label">Category *</label>
                <select name="service_category_id" required class="admin-input">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('service_category_id', $service->service_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('service_category_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="admin-label">Provider</label>
                <select name="service_provider_id" class="admin-input">
                    <option value="">Platform catalog (no provider)</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider->id }}" @selected(old('service_provider_id', $service->service_provider_id) == $provider->id)>{{ $provider->name }}</option>
                    @endforeach
                </select>
                @error('service_provider_id')<p class="field-error">{{ $message }}</p>@enderror
            </div>
            <div>
                @include('admin.partials.form-field', [
                    'label' => 'Starting Price (₹)',
                    'name' => 'starting_price',
                    'type' => 'number',
                    'value' => old('starting_price', $service->starting_price),
                    'required' => true,
                    'inputAttributes' => ['min' => '0', 'step' => '0.01', 'max' => '99999999.99'],
                ])
            </div>
            <div class="flex items-end pb-1">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="status" value="1" {{ old('status', $service->status ?? true) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600">
                    Active
                </label>
            </div>
            <div class="md:col-span-2">
                @include('admin.partials.form-field', [
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'textarea',
                    'limit' => 'description',
                    'value' => old('description', $service->description),
                    'rows' => 4,
                ])
            </div>
        </div>

        @if($service->exists && $service->images->isNotEmpty())
            <div>
                <label class="admin-label">Current Images</label>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach($service->images as $image)
                        <label class="relative block cursor-pointer rounded-lg border border-slate-200 p-2">
                            <img src="{{ asset('storage/'.$image->image_path) }}" alt="" class="h-20 w-full rounded object-cover">
                            <span class="mt-2 flex items-center gap-1 text-xs text-red-600">
                                <input type="checkbox" name="remove_image_ids[]" value="{{ $image->id }}" class="rounded border-slate-300">
                                Remove
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <label class="admin-label">{{ $service->exists ? 'Add Images' : 'Images' }}</label>
            <input type="file" name="images[]" accept="image/*" multiple class="admin-input">
            <p class="field-hint">Up to 5 images. JPG, PNG or WebP.</p>
            @error('images')<p class="field-error">{{ $message }}</p>@enderror
            @error('images.*')<p class="field-error">{{ $message }}</p>@enderror
        </div>

        <div class="flex justify-end gap-2 border-t border-slate-100 pt-5">
            <a href="{{ route('admin.services.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">{{ $service->exists ? 'Update Service' : 'Create Service' }}</button>
        </div>
    </form>
</div>
@endsection
