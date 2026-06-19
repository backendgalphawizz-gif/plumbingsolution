@extends('admin.layouts.app')
@section('title', 'Customers')
@section('page-title', 'Customer Management')
@section('page-subtitle', 'View, create and manage registered customers')

@section('content')
<div class="page-toolbar">
    <div class="stat-tabs">@include('admin.partials._customer-stats', compact('stats'))</div>
    @include('admin.partials.btn-create', ['href' => route('admin.customers.create'), 'label' => 'Add Customer'])
</div>

@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Name, email or mobile..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Account Status</label>
        <select name="is_blocked" class="admin-input">
            <option value="">All accounts</option>
            <option value="0" @selected(request('is_blocked')==='0')>Active only</option>
            <option value="1" @selected(request('is_blocked')==='1')>Blocked only</option>
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', [
    'title' => 'Customer List',
    'meta' => number_format($customers->total()).' customers found',
])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.customers.export')])
    @endslot
    <table class="admin-table">
        <thead><tr>
            <th>Customer</th><th>Contact</th><th>Orders</th><th>Bookings</th><th>Status</th><th>Created Date</th><th>Actions</th>
        </tr></thead>
        <tbody>
            @forelse($customers as $customer)
                <tr>
                    <td>
                        <div class="user-cell">
                            <div class="user-avatar">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                            <div><div class="user-name cell-truncate" title="{{ $customer->name }}">{{ $customer->name }}</div></div>
                        </div>
                    </td>
                    <td>
                        <div class="user-name text-sm">{{ $customer->email }}</div>
                        <div class="user-sub">{{ $customer->mobile ?? 'No mobile' }}</div>
                    </td>
                    <td><span class="font-semibold text-slate-800">{{ $customer->orders_count }}</span></td>
                    <td><span class="font-semibold text-slate-800">{{ $customer->service_bookings_count }}</span></td>
                    <td>@include('admin.partials.status-badge', ['status' => $customer->is_blocked ? 'inactive' : 'active'])</td>
                    <td class="text-sm text-slate-500">{{ $customer->created_at->format('M d, Y') }}</td>
                    <td>
                        <div class="action-group">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="action-btn">View</a>
                            <a href="{{ route('admin.customers.edit', $customer) }}" class="action-btn">Edit</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><div class="empty-state-icon"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div><p>No customers match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $customers->links() }}@endslot
@endcomponent
@endsection
