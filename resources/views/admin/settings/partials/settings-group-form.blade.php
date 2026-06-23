@props([
    'title',
    'description',
    'action',
    'returnTab',
    'group',
    'items',
    'submitLabel' => 'Save Settings',
])

<form action="{{ $action }}" method="POST" class="settings-panel form-card">
    @csrf
    @method('PUT')
    <input type="hidden" name="return_tab" value="{{ $returnTab }}">

    <div class="settings-panel-head">
        <div>
            <h2 class="form-section-title">{{ $title }}</h2>
            <p class="form-section-desc">{{ $description }}</p>
        </div>
    </div>

    <div class="settings-fields">
        @foreach($items as $setting)
            <div class="settings-field">
                <label class="admin-label normal-case" for="setting-{{ $group }}-{{ $setting->key }}">
                    {{ str_replace('_', ' ', ucfirst($setting->key)) }}
                </label>
                @if($setting->type === 'boolean')
                    <select
                        id="setting-{{ $group }}-{{ $setting->key }}"
                        name="settings[{{ $group }}][{{ $setting->key }}]"
                        class="admin-input"
                    >
                        <option value="1" @selected($setting->value === '1' || $setting->value === 'true')>Enabled</option>
                        <option value="0" @selected($setting->value === '0' || $setting->value === 'false')>Disabled</option>
                    </select>
                @else
                    <input
                        id="setting-{{ $group }}-{{ $setting->key }}"
                        name="settings[{{ $group }}][{{ $setting->key }}]"
                        value="{{ $setting->value }}"
                        maxlength="{{ config('admin.limits.setting_value') }}"
                        class="admin-input"
                    >
                @endif
            </div>
        @endforeach
    </div>

    <div class="settings-panel-actions">
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>
</form>
