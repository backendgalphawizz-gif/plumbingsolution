<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Accepted = 'accepted';
    case Started = 'started';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Assigned],
            self::Assigned => [self::Accepted],
            self::Accepted => [self::Started],
            self::Started => [self::Completed],
            self::Completed, self::Cancelled => [],
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
        return in_array($this, [self::Pending, self::Assigned, self::Accepted], true);
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
