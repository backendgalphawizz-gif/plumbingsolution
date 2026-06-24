<?php

namespace App\Enums;

enum NotificationType: string
{
    case Order = 'order';
    case Booking = 'booking';
    case Promotion = 'promotion';
    case System = 'system';
    case Wallet = 'wallet';
}
