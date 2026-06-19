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
}
