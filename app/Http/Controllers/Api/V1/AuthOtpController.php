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
            'type' => ['required', Rule::in(['login', 'register', 'provider_login', 'provider_register'])],
            'app' => ['sometimes', Rule::in(['user', 'provider'])],
        ]);

        $type = $this->resolveOtpType($data);
        $user = User::where('mobile', $data['mobile'])->first();

        if ($type->isLogin()) {
            if ($this->isProviderFlow($data)) {
                if (! $user || $user->role !== UserRole::Provider) {
                    return $this->error('Mobile number is not registered.', 422);
                }
            } elseif (! $user) {
                return $this->error('Mobile number is not registered.', 422);
            }

            if ($user?->is_blocked) {
                return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
            }
        } elseif ($user) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $code = $otp->send($data['mobile'], $type);

        return $this->success(['otp' => $code], 'OTP sent successfully.');
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(array_merge([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
            'type' => ['required', Rule::in(['login', 'register', 'provider_login', 'provider_register'])],
            'app' => ['sometimes', Rule::in(['user', 'provider'])],
        ], $this->fcmTokenRules()));

        $type = $this->resolveOtpType($data);

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type->isLogin()) {
            return $this->isProviderFlow($data)
                ? $this->providerLoginResponse($data['mobile'], $data['fcm_token'] ?? null)
                : $this->userLoginResponse($data['mobile'], $data['fcm_token'] ?? null);
        }

        return $this->success(['verified' => true], 'OTP verified. Please complete registration.');
    }

    private function isProviderFlow(array $data): bool
    {
        if (in_array($data['type'], ['provider_login', 'provider_register'], true)) {
            return true;
        }

        return ($data['app'] ?? 'user') === 'provider';
    }

    private function resolveOtpType(array $data): OtpType
    {
        if ($this->isProviderFlow($data)) {
            return match ($data['type']) {
                'login', 'provider_login' => OtpType::ProviderLogin,
                'register', 'provider_register' => OtpType::ProviderRegister,
            };
        }

        return OtpType::from($data['type']);
    }

    private function userLoginResponse(string $mobile, ?string $fcmToken = null): JsonResponse
    {
        $user = User::with('serviceProvider')->where('mobile', $mobile)->first();

        if (! $user) {
            return $this->success([
                'registered' => false,
                'needs_registration' => true,
            ], 'OTP verified. Please complete registration.');
        }

        if ($user->is_blocked) {
            return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
        }

        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }

    private function providerLoginResponse(string $mobile, ?string $fcmToken = null): JsonResponse
    {
        $user = User::with('serviceProvider')
            ->where('mobile', $mobile)
            ->where('role', UserRole::Provider)
            ->first();

        if (! $user || ! $user->serviceProvider) {
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
