@props([
    'slug',
    'pages',
    'returnTab',
    'title',
    'description',
    'activeAudience' => 'user',
])

@php
    $audiences = [
        'user' => 'User App',
        'vendor' => 'Vendor App',
        'provider' => 'Provider App',
    ];
@endphp

<div class="settings-legal-panel" x-data="{ audience: @js($activeAudience) }">
    <div class="settings-legal-head">
        <div>
            <h2 class="form-section-title">{{ $title }}</h2>
            <p class="form-section-desc">{{ $description }}</p>
        </div>
    </div>

    <div class="settings-subtab-bar">
        @foreach($audiences as $key => $label)
            <button
                type="button"
                class="settings-subtab"
                :class="{ 'is-active': audience === @js($key) }"
                @click="audience = @js($key)"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    @foreach($audiences as $key => $label)
        <div x-show="audience === @js($key)" x-cloak>
            @include('admin.settings.partials.cms-form', [
                'page' => $pages->get($key),
                'returnTab' => $returnTab,
                'audienceLabel' => $label,
                'description' => 'Content shown in the '.$label.' for '.$title.'.',
            ])
        </div>
    @endforeach
</div>
