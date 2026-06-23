@if($user)
<div class="detail-panel">
    <h3 class="detail-panel-title">Customer</h3>
    <div class="user-cell mb-4">
        @if($user->avatar)
            <img src="{{ asset('storage/'.$user->avatar) }}" alt="" class="user-avatar !rounded-full !object-cover">
        @else
            <div class="user-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
        @endif
        <div>
            <div class="user-name">{{ $user->name }}</div>
            <div class="user-sub">{{ $user->mobile ?? 'No mobile' }}</div>
            @if($user->email)<div class="user-sub">{{ $user->email }}</div>@endif
        </div>
    </div>
    @if($user->address)
        <p class="mb-3 text-sm text-slate-600"><span class="font-semibold text-slate-700">Address:</span> {{ $user->address }}</p>
    @endif
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.customers.show', $user) }}" class="action-btn">View Profile</a>
        @if(isset($ordersCount) || isset($bookingsCount))
            <span class="text-xs text-slate-500 self-center">
                {{ $ordersCount ?? 0 }} orders · {{ $bookingsCount ?? 0 }} bookings
            </span>
        @endif
    </div>
</div>
@endif
