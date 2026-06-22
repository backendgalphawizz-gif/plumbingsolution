<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Services\VendorRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    use ApiResponse;

    public function sendOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'type' => ['required', Rule::enum(OtpType::class)],
        ]);

        $type = OtpType::from($data['type']);

        if (! $type->isVendor()) {
            return $this->error('Invalid OTP type for vendor authentication.', 422);
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if ($type->isLogin()) {
            if (! $user || $user->role !== UserRole::Vendor) {
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
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
            'type' => ['required', Rule::enum(OtpType::class)],
        ]);

        $type = OtpType::from($data['type']);

        if (! $type->isVendor()) {
            return $this->error('Invalid OTP type for vendor authentication.', 422);
        }

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type->isLogin()) {
            return $this->loginResponse($data['mobile']);
        }

        return $this->success([
            'verified' => true,
        ], 'OTP verified. Please complete registration.');
    }

    public function login(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'mobile' => V::mobileRules(required: true),
            'otp' => ['required', 'digits:4'],
        ]);

        if (! $otp->verify($data['mobile'], (string) $data['otp'], OtpType::VendorLogin)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        return $this->loginResponse($data['mobile']);
    }

    public function register(Request $request, OtpService $otp, VendorRegistrationService $vendorRegistration): JsonResponse
    {
        if (! $otp->isVerified($request->input('mobile', ''), OtpType::VendorRegister)) {
            return $this->error('Please verify your mobile number with OTP first.', 422);
        }

        $data = $request->validate($vendorRegistration->rules());

        if (User::where('mobile', $data['mobile'])->exists()) {
            return $this->error('Mobile number is already registered.', 422);
        }

        $user = User::create([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'email' => $data['email'] ?? $data['shop_email'] ?? null,
            'address' => $data['address'],
            'role' => UserRole::Vendor,
            'password' => Hash::make(Str::random(32)),
        ]);

        $vendor = $vendorRegistration->createForUser($user, $data, $request);
        $user->setRelation('vendor', $vendor);

        $otp->consumeVerification($data['mobile'], OtpType::VendorRegister);

        $token = $user->createToken('vendor-app', ['vendor'])->plainTextToken;

        return $this->success([
            'user' => VendorApiFormatter::user($user),
            'token' => $token,
        ], 'Registration successful. Your vendor profile is pending admin approval.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    private function loginResponse(string $mobile): JsonResponse
    {
        $user = User::with('vendor')
            ->where('mobile', $mobile)
            ->where('role', UserRole::Vendor)
            ->first();

        if (! $user || ! $user->vendor) {
            return $this->success([
                'registered' => false,
                'needs_registration' => true,
            ], 'OTP verified. Please complete registration.');
        }

        if ($user->is_blocked) {
            return $this->error('Your account has been blocked.', 403, ['reason' => $user->block_reason]);
        }

        $token = $user->createToken('vendor-app', ['vendor'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => VendorApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }
}
