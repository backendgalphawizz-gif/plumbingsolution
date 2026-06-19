<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class OtpService
{
    public function send(string $mobile): string
    {
        $otp = (string) random_int(100000, 999999);

        Cache::put("otp:{$mobile}", $otp, now()->addMinutes(10));

        return $otp;
    }

    public function verify(string $mobile, string $otp): bool
    {
        $cached = Cache::get("otp:{$mobile}");

        if (! $cached || $cached !== $otp) {
            return false;
        }

        Cache::forget("otp:{$mobile}");
        Cache::put("otp_verified:{$mobile}", true, now()->addMinutes(15));

        return true;
    }

    public function isVerified(string $mobile): bool
    {
        return (bool) Cache::get("otp_verified:{$mobile}");
    }

    public function consumeVerification(string $mobile): void
    {
        Cache::forget("otp_verified:{$mobile}");
    }
}
