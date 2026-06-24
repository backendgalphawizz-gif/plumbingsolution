<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Packed = 'packed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Accepted],
            self::Accepted => [self::Packed],
            self::Packed => [self::Shipped],
            self::Shipped => [self::Delivered],
            self::Delivered => [self::Returned],
            self::Returned => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($next === self::Cancelled) {
            return $this->canCancel();
        }

        return $next !== $this && in_array($next, $this->allowedTransitions(), true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Pending, self::Accepted, self::Packed], true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [] && ! $this->canCancel();
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
