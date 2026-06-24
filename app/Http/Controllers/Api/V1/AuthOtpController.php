<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthOtpController extends Controller
{
    use ApiResponse, RegistersFcmToken;

    public function sendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'type' => ['required', Rule::in(['login', 'register'])],
        ]);

        $user = User::where('mobile', $data['mobile'])->first();

        if ($data['type'] === 'login') {
            if (! $user) {
                return $this->error('Mobile number is not registered.', 422);
            }

            if ($user->role === UserRole::Vendor) {
                return $this->error('Please use the vendor app to login.', 422);
            }

            if ($user->is_blocked) {
                return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
            }
        } elseif ($user) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $code = $otp->send($data['mobile'], $this->resolveOtpType($data['type'], $user));

        return $this->success(['otp' => $code], 'OTP sent successfully.');
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(array_merge([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
            'type' => ['required', Rule::in(['login', 'register'])],
        ], $this->fcmTokenRules()));

        $user = User::where('mobile', $data['mobile'])->first();
        $type = $this->resolveOtpType($data['type'], $user);

        if ($data['type'] === 'login' && ! $user) {
            return $this->error('Mobile number is not registered.', 422);
        }

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type->isLogin()) {
            return $user->role === UserRole::Provider
                ? $this->providerLoginResponse($user, $data['fcm_token'] ?? null)
                : $this->userLoginResponse($user, $data['fcm_token'] ?? null);
        }

        return $this->success(['verified' => true], 'OTP verified. Please complete registration.');
    }

    private function resolveOtpType(string $type, ?User $user): OtpType
    {
        if ($type === 'register') {
            return OtpType::Register;
        }

        return $user?->role === UserRole::Provider ? OtpType::ProviderLogin : OtpType::Login;
    }

    private function userLoginResponse(User $user, ?string $fcmToken = null): JsonResponse
    {
        if ($user->is_blocked) {
            return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
        }

        $user->load('serviceProvider');
        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }

    private function providerLoginResponse(User $user, ?string $fcmToken = null): JsonResponse
    {
        $user->load('serviceProvider');

        if (! $user->serviceProvider) {
            return $this->success([
                'registered' => false,
                'needs_registration' => true,
            ], 'OTP verified. Please complete registration.');
        }

        if ($user->is_blocked) {
            return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
        }

        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('provider-app', ['provider'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => ProviderApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }
}
