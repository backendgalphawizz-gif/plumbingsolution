<?php

namespace App\Services;

use App\Enums\OtpType;
use Illuminate\Support\Facades\Cache;

class OtpService
{
    public function send(string $mobile, OtpType $type): string
    {
        $otp = (string) random_int(1000, 9999);

        Cache::put($this->otpKey($mobile, $type), $otp, now()->addMinutes(10));

        return $otp;
    }

    public function verify(string $mobile, string $otp, OtpType $type): bool
    {
        $cached = Cache::get($this->otpKey($mobile, $type));

        if (! $cached || $cached !== $otp) {
            return false;
        }

        Cache::forget($this->otpKey($mobile, $type));
        Cache::put($this->verifiedKey($mobile, $type), true, now()->addMinutes(15));

        return true;
    }

    public function isVerified(string $mobile, OtpType $type): bool
    {
        return (bool) Cache::get($this->verifiedKey($mobile, $type));
    }

    public function consumeVerification(string $mobile, OtpType $type): void
    {
        Cache::forget($this->verifiedKey($mobile, $type));
    }

    private function otpKey(string $mobile, OtpType $type): string
    {
        return "otp:{$type->value}:{$mobile}";
    }

    private function verifiedKey(string $mobile, OtpType $type): string
    {
        return "otp_verified:{$type->value}:{$mobile}";
    }
}
