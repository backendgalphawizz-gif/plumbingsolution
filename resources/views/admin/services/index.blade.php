@extends('admin.layouts.app')
@section('title', 'Services')
@section('page-title', 'Service Management')
@section('page-subtitle', 'Platform and provider-created services')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Service name..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Category</label>
        <select name="category_id" class="admin-input">
            <option value="">All categories</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Provider</label>
        <select name="provider_id" class="admin-input">
            <option value="">All providers</option>
            @foreach($providers as $provider)
                <option value="{{ $provider->id }}" @selected(request('provider_id') == $provider->id)>{{ $provider->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Source</label>
        <select name="source" class="admin-input">
            <option value="">All sources</option>
            <option value="provider" @selected(request('source') === 'provider')>Provider created</option>
            <option value="platform" @selected(request('source') === 'platform')>Platform catalog</option>
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All</option>
            <option value="1" @selected(request('status')==='1')>Active</option>
            <option value="0" @selected(request('status')==='0')>Inactive</option>
        </select>
    </div>
@endcomponent

@component('admin.partials.data-card', ['title' => 'Services', 'meta' => number_format($services->total()).' services found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.services.export')])
    @endslot
    <table class="admin-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Category</th>
                <th>Provider</th>
                <th>Price</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($services as $service)
                <tr>
                    <td>
                        <div class="flex items-center gap-3">
                            @if($service->image)
                                <img src="{{ asset('storage/'.$service->image) }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                            @endif
                            <div>
                                <div class="user-name cell-truncate" title="{{ $service->name }}">{{ $service->name }}</div>
                                <div class="user-sub">{{ \Illuminate\Support\Str::limit($service->description, 40) }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="text-sm">{{ $service->category?->name ?? '—' }}</td>
                    <td class="text-sm">{{ $service->serviceProvider?->name ?? 'Platform' }}</td>
                    <td class="font-semibold">₹{{ number_format($service->starting_price, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $service->status ? 'active' : 'inactive'])</td>
                    <td class="text-sm text-slate-500">{{ $service->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="action-group">
                            <a href="{{ route('admin.services.show', $service) }}" class="action-btn">View</a>
                            <form action="{{ route('admin.services.destroy', $service) }}" method="POST" class="inline" onsubmit="return confirm('Delete this service?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><p>No services match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $services->links() }}@endslot
@endcomponent
@endsection
