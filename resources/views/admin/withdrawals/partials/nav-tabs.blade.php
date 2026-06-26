@props(['active' => 'vendor', 'counts' => []])

<div class="stat-tabs mb-6">
    <a href="{{ route('admin.withdrawals.index', ['type' => 'vendor']) }}" class="stat-tab {{ $active === 'vendor' ? 'active' : '' }}">
        Vendor
        @if(($counts['vendor'] ?? 0) > 0)<span class="count">{{ $counts['vendor'] }}</span>@endif
    </a>
    <a href="{{ route('admin.withdrawals.index', ['type' => 'provider']) }}" class="stat-tab {{ $active === 'provider' ? 'active' : '' }}">
        Provider
        @if(($counts['provider'] ?? 0) > 0)<span class="count">{{ $counts['provider'] }}</span>@endif
    </a>
    <a href="{{ route('admin.withdrawals.index', ['type' => 'user']) }}" class="stat-tab hidden {{ $active === 'user' ? 'active' : '' }}">
        User
        @if(($counts['user'] ?? 0) > 0)<span class="count">{{ $counts['user'] }}</span>@endif
    </a>
</div>
