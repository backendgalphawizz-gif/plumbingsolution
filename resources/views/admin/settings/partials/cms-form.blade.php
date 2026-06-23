@props(['page', 'returnTab', 'description', 'audienceLabel' => null])

@if($page)
    <form action="{{ route('admin.settings.cms.update', $page) }}" method="POST" class="settings-panel form-card">
        @csrf
        @method('PUT')
        <input type="hidden" name="return_tab" value="{{ $returnTab }}">

        <div class="settings-panel-head">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ $page->title }}</h3>
                <p class="form-section-desc">{{ $description }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if($audienceLabel)
                    <span class="settings-slug-badge">{{ $audienceLabel }}</span>
                @endif
                <span class="settings-slug-badge settings-slug-badge-muted">{{ $page->slug }}</span>
            </div>
        </div>

        <div class="space-y-4">
            @include('admin.partials.form-field', [
                'label' => 'Page Title',
                'name' => 'title',
                'limit' => 'cms_title',
                'value' => old('title', $page->title),
                'required' => true,
            ])
            @include('admin.partials.form-field', [
                'label' => 'Content',
                'name' => 'content',
                'type' => 'textarea',
                'limit' => 'cms_content',
                'value' => old('content', $page->content),
                'rows' => 14,
            ])
            <p class="field-hint">HTML is supported. This content is shown in the mobile app.</p>
        </div>

        <div class="settings-panel-actions">
            <button type="submit" class="btn btn-primary">Save {{ $audienceLabel ?? $page->title }}</button>
        </div>
    </form>
@else
    <div class="form-card empty-state">
        <p>This CMS page has not been created yet. Run database seeders to add it.</p>
    </div>
@endif
