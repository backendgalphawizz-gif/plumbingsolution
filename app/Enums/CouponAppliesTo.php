<?php

namespace App\Enums;

enum CouponAppliesTo: string
{
    case Order = 'order';
    case Booking = 'booking';
}
