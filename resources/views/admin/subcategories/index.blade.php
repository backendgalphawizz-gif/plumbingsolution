@extends('admin.layouts.app')
@section('title', 'Subcategory Management')
@section('page-title', 'Subcategory Management')
@section('page-subtitle', 'Create and manage subcategories under parent categories')

@section('content')
<div x-data="{ openSubcategory: null, editSubcategory: null, selectedCategory: '{{ request('category_id') ?? $categories->first()?->id ?? '' }}' }">
    @component('admin.categories.partials.nav-tabs', [
        'active' => 'subcategories',
        'categoryCount' => $counts['categories'],
        'subcategoryCount' => $counts['subcategories'],
    ])
        <button type="button" @click="openSubcategory = 'new'" class="btn btn-primary" @if($categories->isEmpty()) disabled @endif>
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Subcategory
        </button>
    @endcomponent

    @if($categories->isEmpty())
        <div class="alert alert-error">
            <span>No categories exist yet. <a href="{{ route('admin.categories.index') }}" class="font-semibold underline">Create a category first</a> before adding subcategories.</span>
        </div>
    @endif

    @component('admin.partials.filter-panel')
        <div class="filter-field">
            <label class="admin-label">Search</label>
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Subcategory name..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
        </div>
        <div class="filter-field">
            <label class="admin-label">Parent Category</label>
            <select name="category_id" class="admin-input">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
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
        'title' => 'Subcategories',
        'meta' => number_format($subcategories->total()).' subcategories found',
    ])
        @slot('actions')
            @include('admin.partials.export-dropdown', ['route' => route('admin.subcategories.export')])
        @endslot
        <table class="admin-table">
            <thead><tr>
                <th>Subcategory</th><th>Parent Category</th><th>Slug</th><th>Products</th><th>Sort</th><th>Status</th><th>Created Date</th><th>Actions</th>
            </tr></thead>
            <tbody>
                @forelse($subcategories as $sub)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="category-thumb !h-10 !w-10">
                                    @if($sub->image)
                                        <img src="{{ asset('storage/'.$sub->image) }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <span class="text-sm font-bold text-slate-400">{{ strtoupper(substr($sub->name, 0, 1)) }}</span>
                                    @endif
                                </div>
                                <div class="user-name">{{ $sub->name }}</div>
                            </div>
                        </td>
                        <td>
                            <a href="{{ route('admin.subcategories.index', ['category_id' => $sub->category_id]) }}" class="text-sm font-medium text-emerald-600 hover:underline">
                                {{ $sub->category?->name ?? '—' }}
                            </a>
                        </td>
                        <td class="text-sm text-slate-500">{{ $sub->slug }}</td>
                        <td><span class="font-semibold">{{ $sub->products_count }}</span></td>
                        <td class="text-sm text-slate-500">{{ $sub->sort_order }}</td>
                        <td>@include('admin.partials.status-badge', ['status' => $sub->status ? 'active' : 'inactive'])</td>
                        <td class="text-sm text-slate-500">{{ $sub->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="action-group">
                                <button @click="editSubcategory = {{ $sub->id }}" class="action-btn">Edit</button>
                                <form action="{{ route('admin.subcategories.destroy', $sub) }}" method="POST" onsubmit="return confirm('Delete this subcategory?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state"><p>No subcategories found.@if($categories->isNotEmpty()) Click "Add Subcategory" to create one.@endif</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
        @slot('footer'){{ $subcategories->links() }}@endslot
    @endcomponent

    @foreach($subcategories as $sub)
        <div x-show="editSubcategory === {{ $sub->id }}" x-cloak class="modal-backdrop">
            <div @click.outside="editSubcategory = null" class="modal-card">
                <h3 class="modal-title">Edit Subcategory</h3>
                <p class="modal-sub">Parent: {{ $sub->category?->name }}</p>
                <form action="{{ route('admin.subcategories.update', $sub) }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')
                    @include('admin.categories.partials.subcategory-fields', ['subcategory' => $sub])
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="editSubcategory = null" class="btn btn-secondary btn-sm">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div x-show="openSubcategory === 'new'" x-cloak class="modal-backdrop">
        <div @click.outside="openSubcategory = null" class="modal-card">
            <h3 class="modal-title">Add Subcategory</h3>
            <form
                x-ref="createForm"
                method="POST"
                enctype="multipart/form-data"
                class="space-y-4"
                @submit.prevent="
                    if (!selectedCategory) { alert('Please select a parent category.'); return; }
                    $refs.createForm.action = '{{ url('admin/categories') }}/' + selectedCategory + '/subcategories';
                    $refs.createForm.submit();
                "
            >
                @csrf
                <div>
                    <label class="admin-label">Parent Category *</label>
                    <select x-model="selectedCategory" required class="admin-input">
                        <option value="">Select category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                @include('admin.categories.partials.subcategory-fields', ['subcategory' => null])
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openSubcategory = null" class="btn btn-secondary btn-sm">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
