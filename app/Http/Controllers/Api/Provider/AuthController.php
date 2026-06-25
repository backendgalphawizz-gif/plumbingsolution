<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\OtpType;
use App\Enums\ProviderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    use ApiResponse, RegistersFcmToken;

    public function sendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'type' => ['required', Rule::in(['login', 'register'])],
        ]);

        $type = $this->resolveOtpType($data['type']);

        $user = User::where('mobile', $data['mobile'])->first();

        if ($type->isLogin()) {
            if (! $user || $user->role !== UserRole::Provider) {
                return $this->error('Mobile number is not registered.', 422);
            }

            if ($user->is_blocked) {
                return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
            }

            if ($response = $this->ensureProviderCanLogin($user)) {
                return $response;
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
            'type' => ['required', Rule::in(['login', 'register'])],
        ], $this->fcmTokenRules()));

        $type = $this->resolveOtpType($data['type']);

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type->isLogin()) {
            return $this->loginResponse($data['mobile'], $data['fcm_token'] ?? null);
        }

        return $this->success(['verified' => true], 'OTP verified. Please complete registration.');
    }

    public function login(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(array_merge([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
        ], $this->fcmTokenRules()));

        if (! $otp->verify($data['mobile'], (string) $data['otp'], OtpType::ProviderLogin)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        return $this->loginResponse($data['mobile'], $data['fcm_token'] ?? null);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->clearFcmToken($request->user());
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    private function loginResponse(string $mobile, ?string $fcmToken = null): JsonResponse
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

        if ($response = $this->ensureProviderCanLogin($user)) {
            return $response;
        }

        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('provider-app', ['provider'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => ProviderApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }

    private function resolveOtpType(string $type): OtpType
    {
        return match ($type) {
            'login' => OtpType::ProviderLogin,
            'register' => OtpType::ProviderRegister,
        };
    }

    private function ensureProviderCanLogin(User $user): ?JsonResponse
    {
        $user->loadMissing('serviceProvider');

        if (! $user->serviceProvider) {
            return null;
        }

        if ($user->serviceProvider->status === ProviderStatus::Approved) {
            return null;
        }

        return match ($user->serviceProvider->status) {
            ProviderStatus::Rejected => $this->error('Your provider account was rejected.', 403, [
                'reason' => $user->serviceProvider->rejection_reason,
            ]),
            ProviderStatus::Suspended => $this->error('Your provider account has been suspended.', 403),
            default => $this->error('Your provider account is pending admin approval.', 403),
        };
    }
}
