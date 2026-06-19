<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Razorpay = 'razorpay';
    case PhonePe = 'phonepe';
    case Cod = 'cod';
}
