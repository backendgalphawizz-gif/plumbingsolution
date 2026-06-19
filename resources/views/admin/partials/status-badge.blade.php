@props(['status'])

@php
    $value = is_object($status) ? ($status->value ?? (string) $status) : (string) $status;
    $value = strtolower($value);
    $map = [
        'active' => 'badge-success', 'approved' => 'badge-success', 'completed' => 'badge-success', 'delivered' => 'badge-success',
        'inactive' => 'badge-danger', 'rejected' => 'badge-danger', 'cancelled' => 'badge-danger', 'failed' => 'badge-danger', 'blocked' => 'badge-danger', 'suspended' => 'badge-neutral',
        'pending' => 'badge-warning', 'assigned' => 'badge-info', 'accepted' => 'badge-info', 'packed' => 'badge-info', 'shipped' => 'badge-info',
        'refunded' => 'badge-neutral', 'returned' => 'badge-warning',
    ];
    $class = $map[$value] ?? 'badge-neutral';
@endphp

<span class="badge {{ $class }}">{{ str_replace('_', ' ', $value) }}</span>
