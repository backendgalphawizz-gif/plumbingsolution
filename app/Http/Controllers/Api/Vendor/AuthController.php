<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Http\Controllers\Api\Concerns\RegistersFcmToken;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use App\Services\OtpService;
use App\Services\VendorRegistrationService;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            if ($response = $this->ensureVendorCanLogin($user)) {
                return $response;
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

        if (! $type->isVendor()) {
            return $this->error('Invalid OTP type for vendor authentication.', 422);
        }

        if (! $otp->verify($data['mobile'], (string) $data['otp'], $type)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        if ($type->isLogin()) {
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

        if (! $otp->verify($data['mobile'], (string) $data['otp'], OtpType::VendorLogin)) {
            return $this->error('Invalid or expired OTP.', 422);
        }

        return $this->loginResponse($data['mobile'], $data['fcm_token'] ?? null);
    }

    public function register(Request $request, OtpService $otp, VendorRegistrationService $vendorRegistration): JsonResponse
    {
        if (! $otp->isVerified($request->input('mobile', ''), OtpType::VendorRegister)) {
            return $this->error('Please verify your mobile number with OTP first.', 422);
        }

        $request->merge([
            'email' => $request->filled('email') ? trim((string) $request->input('email')) : null,
            'shop_email' => $request->filled('shop_email') ? trim((string) $request->input('shop_email')) : null,
            'gst_number' => $request->filled('gst_number') ? strtoupper(trim((string) $request->input('gst_number'))) : null,
        ]);

        $data = $request->validate(array_merge($vendorRegistration->rules(), $this->fcmTokenRules()));

        $user = DB::transaction(function () use ($data, $request, $vendorRegistration) {
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

            return $user;
        });

        $otp->consumeVerification($data['mobile'], OtpType::VendorRegister);

        $this->saveFcmToken($user, $data['fcm_token'] ?? null);

        $token = $user->createToken('vendor-app', ['vendor'])->plainTextToken;

        return $this->success([
            'user' => VendorApiFormatter::user($user),
            'token' => $token,
        ], 'Registration successful. Your vendor profile is pending admin approval.', 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->clearFcmToken($request->user());
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    private function loginResponse(string $mobile, ?string $fcmToken = null): JsonResponse
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

        if ($response = $this->ensureVendorCanLogin($user)) {
            return $response;
        }

        $this->saveFcmToken($user, $fcmToken);

        $token = $user->createToken('vendor-app', ['vendor'])->plainTextToken;

        return $this->success([
            'registered' => true,
            'user' => VendorApiFormatter::user($user),
            'token' => $token,
        ], 'Login successful.');
    }

    private function ensureVendorCanLogin(User $user): ?JsonResponse
    {
        $user->loadMissing('vendor');

        if (! $user->vendor) {
            return null;
        }

        if ($user->vendor->status === VendorStatus::Approved) {
            return null;
        }

        return match ($user->vendor->status) {
            VendorStatus::Rejected => $this->error('Your vendor account was rejected.', 403, [
                'reason' => $user->vendor->rejection_reason,
            ]),
            VendorStatus::Suspended => $this->error('Your vendor account has been suspended.', 403),
            default => $this->error('Your vendor account is pending admin approval.', 403),
        };
    }
}
