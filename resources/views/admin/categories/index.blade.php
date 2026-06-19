@extends('admin.layouts.app')
@section('title', 'Category Management')
@section('page-title', 'Category Management')
@section('page-subtitle', 'Create and manage product categories')

@section('content')
<div x-data="{ openCategory: null, editCategory: null }">
    @component('admin.categories.partials.nav-tabs', [
        'active' => 'categories',
        'categoryCount' => $counts['categories'],
        'subcategoryCount' => $counts['subcategories'],
    ])
        <button type="button" @click="openCategory = 'new'" class="btn btn-primary">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Category
        </button>
    @endcomponent

    @component('admin.partials.filter-panel')
        <div class="filter-field">
            <label class="admin-label">Search</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Category name..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
        </div>
        <div class="filter-field">
            <label class="admin-label">Status</label>
            <select name="status" class="admin-input">
                <option value="">All statuses</option>
                <option value="1" @selected(request('status')==='1')>Active</option>
                <option value="0" @selected(request('status')==='0')>Inactive</option>
            </select>
        </div>
        @include('admin.partials.date-filters')
    @endcomponent

    @component('admin.partials.data-card', [
        'title' => 'Categories',
        'meta' => number_format($categories->total()).' categories found',
    ])
        @slot('actions')
            @include('admin.partials.export-dropdown', ['route' => route('admin.categories.export')])
        @endslot
        <table class="admin-table">
            <thead><tr>
                <th>Category</th><th>Slug</th><th>Subcategories</th><th>Products</th><th>Sort</th><th>Status</th><th>Created Date</th><th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="category-thumb !h-10 !w-10">
                                    @if($category->image)
                                        <img src="{{ asset('storage/'.$category->image) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-sm font-bold text-slate-400">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="user-name">{{ $category->name }}</div>
                            </div>
                        </td>
                        <td class="text-sm text-slate-500">{{ $category->slug }}</td>
                        <td>
                            <a href="{{ route('admin.subcategories.index', ['category_id' => $category->id]) }}" class="font-semibold text-emerald-600 hover:underline">
                                {{ $category->subcategories_count }}
                            </a>
                        </td>
                        <td><span class="font-semibold">{{ $category->products_count }}</span></td>
                        <td class="text-sm text-slate-500">{{ $category->sort_order }}</td>
                        <td>@include('admin.partials.status-badge', ['status' => $category->status ? 'active' : 'inactive'])</td>
                        <td class="text-sm text-slate-500">{{ $category->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="action-group">
                                <button @click="editCategory = {{ $category->id }}" class="action-btn">Edit</button>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><p>No categories found. Create your first category.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        @slot('footer'){{ $categories->links() }}@endslot
    @endcomponent

    @foreach($categories as $category)
        <div x-show="editCategory === {{ $category->id }}" x-cloak class="modal-backdrop">
            <div @click.outside="editCategory = null" class="modal-card">
                <h3 class="modal-title">Edit Category</h3>
                <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    @include('admin.categories.partials.category-fields', ['category' => $category])
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editCategory = null" class="btn btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div x-show="openCategory === 'new'" x-cloak class="modal-backdrop">
        <div @click.outside="openCategory = null" class="modal-card">
            <h3 class="modal-title">Add Category</h3>
            <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @include('admin.categories.partials.category-fields')
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCategory = null" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
