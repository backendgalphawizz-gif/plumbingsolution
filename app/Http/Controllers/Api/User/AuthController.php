<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ProviderRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    public function register(Request $request, OtpService $otp, ProviderRegistrationService $providerRegistration): JsonResponse
    {
        if (! $otp->isVerified($request->input('mobile', ''), OtpType::Register)) {
            return $this->error('Please verify your mobile number with OTP first.', 422);
        }

        $rules = [
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::emailRules(required: false, uniqueTable: 'users'),
            'address' => V::addressRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
        ];

        if ($providerRegistration->isProviderRole($request->input('role'))) {
            $providerRegistration->normalizeSkills($request);
            $rules['address'] = ['required', 'string', V::maxRule('address')];
            $rules = array_merge($rules, $providerRegistration->rules());
        }

        $data = $request->validate(array_merge($rules, $this->fcmTokenRules()));

        $response = $this->registerResponse($data, $request, $providerRegistration, $data['fcm_token'] ?? null);

        $otp->consumeVerification($data['mobile'], OtpType::Register);

        return $response;
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

    private function registerResponse(array $data, Request $request, ProviderRegistrationService $providerRegistration, ?string $fcmToken = null): JsonResponse
    {
        if (User::where('mobile', $data['mobile'])->exists()) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'role' => $data['role'],
            'password' => Hash::make(Str::random(32)),
        ]);

        if ($providerRegistration->isProviderRole($data['role'])) {
            $provider = $providerRegistration->createForUser($user, $data, $request);
            $user->setRelation('serviceProvider', $provider);
        }

        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        $message = $providerRegistration->isProviderRole($data['role'])
            ? 'Registration successful. Your provider profile is pending admin approval.'
            : 'Registration successful.';

        return $this->success([
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], $message, 201);
    }
}
