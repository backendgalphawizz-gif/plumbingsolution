@extends('admin.layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')
@section('page-subtitle', 'App configuration, commission rates and CMS pages')

@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <form action="{{ route('admin.settings.update') }}" method="POST" class="form-card">@csrf @method('PUT')
        <div class="form-section-title">App & Commission Settings</div>
        <div class="form-section-desc">Configure platform-wide settings and fee structures.</div>
        @foreach($settings as $group => $items)
            <div class="mb-5">
                <p class="admin-label mb-3">{{ $group }}</p>
                @foreach($items as $setting)
                    <div class="mb-3">
                        <label class="admin-label normal-case">{{ str_replace('_', ' ', ucfirst($setting->key)) }}</label>
                        <input name="settings[{{ $group }}][{{ $setting->key }}]" value="{{ $setting->value }}" maxlength="{{ config('admin.limits.setting_value') }}" class="admin-input">
                    </div>
                @endforeach
            </div>
        @endforeach
        <button class="btn btn-primary">Save Settings</button>
    </form>

    <div class="space-y-4">
        @foreach($cmsPages as $page)
            <form action="{{ route('admin.settings.cms.update', $page) }}" method="POST" class="form-card">@csrf @method('PUT')
                <div class="form-section-title">{{ $page->title }}</div>
                <div class="space-y-3">
                    @include('admin.partials.form-field', ['label' => 'Page Title', 'name' => 'title', 'limit' => 'cms_title', 'value' => old('title', $page->title), 'required' => true])
                    @include('admin.partials.form-field', ['label' => 'Content', 'name' => 'content', 'type' => 'textarea', 'limit' => 'cms_content', 'value' => old('content', $page->content), 'rows' => 4])
                </div>
                <button class="btn btn-secondary btn-sm mt-4">Update CMS Page</button>
            </form>
        @endforeach
    </div>
</div>
@endsection
