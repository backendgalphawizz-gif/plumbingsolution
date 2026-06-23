<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ProviderRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
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
            'type' => ['required', Rule::in(['login', 'register', 'provider_login', 'provider_register'])],
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

    public function register(Request $request, OtpService $otp, ProviderRegistrationService $providerRegistration): JsonResponse
    {
        if (! $otp->isVerified($request->input('mobile', ''), OtpType::ProviderRegister)) {
            return $this->error('Please verify your mobile number with OTP first.', 422);
        }

        $providerRegistration->normalizeSkills($request);

        $data = $request->validate(array_merge([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::emailRules(required: false, uniqueTable: 'users'),
            'address' => ['required', 'string', V::maxRule('address')],
        ], $providerRegistration->rules(), $this->fcmTokenRules()));

        if (User::where('mobile', $data['mobile'])->exists()) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'],
            'role' => UserRole::Provider,
            'password' => Hash::make(Str::random(32)),
        ]);

        $provider = $providerRegistration->createForUser($user, $data, $request);
        $user->setRelation('serviceProvider', $provider);

        $otp->consumeVerification($data['mobile'], OtpType::ProviderRegister);

        $this->saveFcmToken($user, $data['fcm_token'] ?? null);

        $token = $user->createToken('provider-app', ['provider'])->plainTextToken;

        return $this->success([
            'user' => ProviderApiFormatter::user($user),
            'token' => $token,
        ], 'Registration successful. Your provider profile is pending admin approval.', 201);
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
            'login', 'provider_login' => OtpType::ProviderLogin,
            'register', 'provider_register' => OtpType::ProviderRegister,
        };
    }
}
