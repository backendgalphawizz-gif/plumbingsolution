@extends('admin.layouts.app')
@section('title', 'Withdrawal Requests')
@section('page-title', 'Withdrawal Requests')
@section('page-subtitle', 'Review and process vendor, provider and user payout requests')

@section('content')
@include('admin.withdrawals.partials.nav-tabs', ['active' => $type, 'counts' => $counts])

@component('admin.partials.filter-panel')
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="filter-field">
        <label class="admin-label">Search</label>
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Transaction ID, name, mobile..." class="admin-input" maxlength="{{ config('admin.limits.search') }}">
    </div>
    <div class="filter-field">
        <label class="admin-label">Status</label>
        <select name="status" class="admin-input">
            <option value="">All statuses</option>
            @foreach(['pending','paid','rejected'] as $s)
                <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    @include('admin.partials.date-filters')
@endcomponent

@component('admin.partials.data-card', ['title' => ucfirst($type).' Withdrawals', 'meta' => number_format($withdrawals->total()).' requests found'])
    <table class="admin-table">
        <thead>
            <tr>
                <th>Transaction ID</th>
                <th>{{ $type === 'vendor' ? 'Shop / Owner' : ($type === 'provider' ? 'Provider' : 'User') }}</th>
                <th>Mobile</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Requested</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($withdrawals as $withdrawal)
                @php
                    $name = match ($type) {
                        'provider' => $withdrawal->serviceProvider?->name,
                        'user' => $withdrawal->user?->name,
                        default => trim(($withdrawal->vendor?->shop_name ?? '').($withdrawal->vendor?->owner_name ? ' · '.$withdrawal->vendor->owner_name : '')),
                    };
                    $mobile = match ($type) {
                        'provider' => $withdrawal->serviceProvider?->mobile,
                        'user' => $withdrawal->user?->mobile,
                        default => $withdrawal->vendor?->mobile,
                    };
                @endphp
                <tr>
                    <td class="font-semibold text-slate-800">{{ $withdrawal->transaction_id }}</td>
                    <td>{{ $name ?: '—' }}</td>
                    <td>{{ $mobile ?: '—' }}</td>
                    <td class="font-semibold">₹{{ number_format($withdrawal->amount, 2) }}</td>
                    <td>@include('admin.partials.status-badge', ['status' => $withdrawal->status])</td>
                    <td class="text-sm text-slate-500">{{ $withdrawal->created_at->format('M d, Y • g:i A') }}</td>
                    <td>
                        <a href="{{ route('admin.withdrawals.show', ['type' => $type, 'withdrawal' => $withdrawal->id]) }}" class="action-btn">View</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="empty-state"><p>No withdrawal requests match your filters.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
    @slot('footer'){{ $withdrawals->links() }}@endslot
@endcomponent
@endsection
