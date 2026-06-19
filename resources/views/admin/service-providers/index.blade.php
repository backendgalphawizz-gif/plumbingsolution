@extends('admin.layouts.app')
@section('title', 'Service Providers')
@section('page-title', 'Service Provider Management')
@section('page-subtitle', 'Manage plumbers, approve profiles and assign to bookings')

@section('content')
<div class="page-toolbar">
    <div class="stat-tabs">@include('admin.partials._provider-stats', compact('stats'))</div>
    @include('admin.partials.btn-create', ['href' => route('admin.service-providers.create'), 'label' => 'Add Provider'])
</div>

@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, mobile or area..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','approved','rejected','suspended'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="filter-field">
        <label class="admin-label">Min. Experience</label>
        <input type="number" name="min_experience" min="0" value="{{ request('min_experience') }}" placeholder="Years" class="admin-input">
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Provider List', 'meta' => number_format($providers->total()).' providers found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.service-providers.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Provider</th><th>Mobile</th><th>Skills</th><th>Experience</th><th>Status</th><th>Bookings</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($providers as $provider)
                <tr>
                    <td><div class="user-cell"><div class="user-avatar">{{ strtoupper(substr($provider->name, 0, 1)) }}</div><div class="user-name cell-truncate" title="{{ $provider->name }}">{{ $provider->name }}</div></div></td>
                    <td>{{ $provider->mobile }}</td>
                    <td class="max-w-[200px] truncate text-sm text-slate-500">{{ implode(', ', $provider->skills ?? []) ?: '—' }}</td>
                    <td>{{ $provider->experience_years }} yrs</td>
                    <td>@include('admin.partials.status-badge', ['status' => $provider->status])</td>
                    <td><span class="font-semibold">{{ $provider->bookings_count }}</span></td>
                    <td class="text-sm text-slate-500">{{ $provider->created_at->format('M d, Y') }}</td>
                    <td><div class="action-group"><a href="{{ route('admin.service-providers.show', $provider) }}" class="action-btn">View</a><a href="{{ route('admin.service-providers.edit', $provider) }}" class="action-btn">Edit</a></div></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><p>No providers match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $providers->links() }}@endslot
@endcomponent
@endsection
