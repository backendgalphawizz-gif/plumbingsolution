<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function sendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate(['mobile' => V::mobileRules(required: true)]);

        $code = $otp->send($data['mobile']);

        $payload = ['message' => 'OTP sent successfully.'];
        if (config('app.debug')) {
            $payload['debug_otp'] = $code;
        }

        return $this->success($payload);
    }

    public function login(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'string', 'size:6'],
        ]);

        if (! $otp->verify($data['mobile'], $data['otp'])) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if (! $user) {
            return $this->success([
                'registered' => false,
                'needs_registration' => true,
            ], 'OTP verified. Please complete registration.');
        }

        if ($user->is_blocked) {
            return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
        }

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }

    public function register(Request $request, OtpService $otp): JsonResponse
    {
        if (! $otp->isVerified($request->input('mobile', ''))) {
            return $this->error('Please verify your mobile number with OTP first.', 422);
        }

        $data = $request->validate([
            'name' => V::nameRules(),
            'mobile' => array_merge(V::mobileRules(required: true), ['unique:users,mobile']),
            'email' => V::emailRules(required: false, uniqueTable: 'users'),
            'address' => V::addressRules(),
        ]);

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => Hash::make(Str::random(32)),
        ]);

        $otp->consumeVerification($data['mobile']);

        $token = $user->createToken('user-app', ['user'])->plainTextToken;

        return $this->success([
            'user' => UserApiFormatter::user($user),
            'token' => $token,
        ], 'Registration successful.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }
}
