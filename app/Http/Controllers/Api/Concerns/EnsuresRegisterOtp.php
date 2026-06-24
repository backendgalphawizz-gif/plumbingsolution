<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Enums\OtpType;
use App\Http\Traits\ApiResponse;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait EnsuresRegisterOtp
{
    protected function ensureRegisterOtpVerified(
        Request $request,
        OtpService $otp,
        OtpType $type,
        array $conflicts = [],
    ): ?JsonResponse {
        $mobile = (string) $request->input('mobile', '');

        if ($otp->isVerified($mobile, $type)) {
            return null;
        }

        if ($request->filled('otp')) {
            if ($otp->verify($mobile, (string) $request->input('otp'), $type)) {
                return null;
            }

            return $this->error('Invalid or expired OTP.', 422);
        }

        foreach ($conflicts as $conflict) {
            if ($otp->isVerified($mobile, $conflict['type'])) {
                return $this->error(
                    "OTP was verified for {$conflict['label']} registration. Send OTP again for this app and verify before registering.",
                    422,
                );
            }
        }

        return $this->error($this->registerOtpRequiredMessage($type), 422);
    }

    protected function registerOtpRequiredMessage(OtpType $type): string
    {
        return match ($type) {
            OtpType::Register => 'Please verify your mobile number first using POST /api/v1/auth/send-otp with type "register", then verify-otp or include otp in this request.',
            OtpType::ProviderRegister => 'Please verify your mobile number first using POST /api/v1/auth/send-otp with type "register", then verify-otp or include otp in this request.',
            OtpType::VendorRegister => 'Please verify your mobile number first using POST /api/v2/auth/send-otp with type "vendor_register", then verify-otp or include otp in this request.',
            default => 'Please verify your mobile number with OTP first.',
        };
    }
}
