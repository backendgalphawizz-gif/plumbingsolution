<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Http\Controllers\Api\Concerns\EnsuresRegisterOtp;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Services\ProviderRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthRegisterController extends Controller
{
    use ApiResponse, EnsuresRegisterOtp, RegistersFcmToken;

    public function register(Request $request, OtpService $otp, ProviderRegistrationService $providerRegistration): JsonResponse
    {
        if ($request->is('api/user/*')) {
            $request->merge(['role' => UserRole::Customer->value]);
        }

        $request->merge([
            'email' => $request->filled('email') ? trim((string) $request->input('email')) : null,
        ]);

        if ($response = $this->ensureRegisterOtpVerified($request, $otp, OtpType::Register)) {
            return $response;
        }

        $isProvider = $providerRegistration->isProviderRole($request->input('role'));

        if ($isProvider) {
            $providerRegistration->normalizeSkills($request);
        }

        $rules = [
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::registrationEmailRules(uniqueTable: 'users'),
            'role' => ['required', Rule::in([UserRole::Customer->value, UserRole::Provider->value])],
            'otp' => ['sometimes', 'digits:4'],
        ];

        if ($isProvider) {
            $rules['address'] = ['required', 'string', V::maxRule('address')];
            $rules = array_merge($rules, $providerRegistration->rules());
        } else {
            $rules['address'] = V::addressRules();
        }

        $data = $request->validate(array_merge($rules, $this->fcmTokenRules()));

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

        if ($isProvider) {
            $provider = $providerRegistration->createForUser($user, $data, $request);
            $user->setRelation('serviceProvider', $provider);
        }

        $otp->consumeVerification($data['mobile'], OtpType::Register);
        $this->saveFcmToken($user, $data['fcm_token'] ?? null);

        if ($isProvider) {
            $token = $user->createToken('provider-app', ['provider'])->plainTextToken;

            return $this->success([
                'user' => ProviderApiFormatter::user($user),
                'token' => $token,
            ], 'Registration successful. Your provider profile is pending admin approval.', 201);
        }

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        return $this->success([
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], 'Registration successful.', 201);
    }
}
