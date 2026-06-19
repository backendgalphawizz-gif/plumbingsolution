<a href="{{ route('admin.customers.index') }}" class="stat-tab {{ !request('is_blocked') && !request('search') ? 'active' : '' }}">
    All <span class="count">{{ $stats['total'] }}</span>
</a>
<a href="{{ route('admin.customers.index', ['is_blocked' => 0]) }}" class="stat-tab {{ request('is_blocked')==='0' ? 'active' : '' }}">
    Active <span class="count">{{ $stats['active'] }}</span>
</a>
<a href="{{ route('admin.customers.index', ['is_blocked' => 1]) }}" class="stat-tab danger {{ request('is_blocked')==='1' ? 'active' : '' }}">
    Blocked <span class="count">{{ $stats['blocked'] }}</span>
</a>
