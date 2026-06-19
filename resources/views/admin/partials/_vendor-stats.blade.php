<a href="{{ route('admin.vendors.index') }}" class="stat-tab {{ !request('status') ? 'active' : '' }}">All <span class="count">{{ $stats['total'] }}</span></a>
<a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="stat-tab warning {{ request('status')==='pending' ? 'active' : '' }}">Pending <span class="count">{{ $stats['pending'] }}</span></a>
<a href="{{ route('admin.vendors.index', ['status' => 'approved']) }}" class="stat-tab {{ request('status')==='approved' ? 'active' : '' }}">Approved <span class="count">{{ $stats['approved'] }}</span></a>
<a href="{{ route('admin.vendors.index', ['status' => 'suspended']) }}" class="stat-tab danger {{ request('status')==='suspended' ? 'active' : '' }}">Suspended <span class="count">{{ $stats['suspended'] }}</span></a>
