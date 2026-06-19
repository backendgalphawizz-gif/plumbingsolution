@extends('admin.layouts.app')
@section('title', 'Service Bookings')
@section('page-title', 'Service Booking Management')
@section('page-subtitle', 'Assign providers and track booking progress')

@section('content')
@component('admin.partials.filter-panel')
    <div class="filter-field">
        <label class="admin-label">Booking Number</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search booking #..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','assigned','accepted','started','completed','cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => 'Bookings', 'meta' => number_format($bookings->total()).' bookings found'])
    @slot('actions')
        @include('admin.partials.export-dropdown', ['route' => route('admin.service-bookings.export')])
    @endslot
    <table class="admin-table">
        <thead><tr><th>Booking #</th><th>Service</th><th>Customer</th><th>Provider</th><th>Amount</th><th>Status</th><th>Created Date</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($bookings as $booking)
                <tr>
                    <td class="font-semibold text-slate-800">{{ $booking->booking_number }}</td>
                    <td>{{ $booking->service_name }}</td>
                    <td>{{ $booking->user?->name }}</td>
                    <td>{{ $booking->serviceProvider?->name ?? '—' }}</td>
                    <td class="font-semibold">₹{{ number_format($booking->amount, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $booking->status])</td>
                    <td class="text-sm text-slate-500">{{ $booking->created_at->format('M d, Y') }}</td>
                    <td><a href="{{ route('admin.service-bookings.show', $booking) }}" class="action-btn">Manage</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><p>No bookings match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $bookings->links() }}@endslot
@endcomponent
@endsection
