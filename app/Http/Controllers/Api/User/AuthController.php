<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\OtpType;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
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
            'type' => ['required', Rule::enum(OtpType::class)],
        ]);

        $type = OtpType::from($data['type']);

        if (! $type->isUser()) {
            return $this->error('Invalid OTP type for user authentication.', 422);
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if ($type === OtpType::Login) {
            if (! $user) {
                return $this->error('Mobile number is not registered.', 422);
            }

            if ($user->is_blocked) {
                return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
            }
        } elseif ($user) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $code = $otp->send($data['mobile'], $type);

        return $this->success([
            'otp' => $code,
        ], 'OTP sent successfully.');
    }

    public function verifyOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(array_merge([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
            'type' => ['required', Rule::enum(OtpType::class)],
        ], $this->fcmTokenRules()));

        $type = OtpType::from($data['type']);

        if (! $type->isUser()) {
            return $this->error('Invalid OTP type for user authentication.', 422);
        }

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type === OtpType::Login) {
            return $this->loginResponse($data['mobile'], $data['fcm_token'] ?? null);
        }

        return $this->success([
            'verified' => true,
        ], 'OTP verified. Please complete registration.');
    }

    public function login(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(array_merge([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
        ], $this->fcmTokenRules()));

        if (! $otp->verify($data['mobile'], (string) $data['otp'], OtpType::Login)) {
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
}
