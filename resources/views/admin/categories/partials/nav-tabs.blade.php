@props(['active' => 'categories'])

<div class="page-toolbar">
    <div class="stat-tabs">
        <a href="{{ route('admin.categories.index') }}" class="stat-tab {{ $active === 'categories' ? 'active' : '' }}">
            Categories
            @isset($categoryCount)<span class="count">{{ $categoryCount }}</span>@endisset
        </a>
        <a href="{{ route('admin.subcategories.index') }}" class="stat-tab {{ $active === 'subcategories' ? 'active' : '' }}">
            Subcategories
            @isset($subcategoryCount)<span class="count">{{ $subcategoryCount }}</span>@endisset
        </a>
    </div>
    {{ $slot }}
</div>
