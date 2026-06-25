<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    use ApiResponse;

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $admin = Admin::where('email', $credentials['email'])->first();

        if (! $admin || ! Hash::check($credentials['password'], $admin->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $admin->is_active) {
            return $this->error('Account deactivated.', 403);
        }

        $token = $admin->createToken('admin-token', ['admin'])->plainTextToken;

        return $this->success([
            'admin' => $admin->load('roles'),
            'token' => $token,
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success($request->user()->load('roles'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'string'],
            'role_title' => ['nullable', 'string', 'max:255'],
        ]);

        $admin->update($data);

        return $this->success($admin->fresh(), 'Profile updated.');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::defaults()],
        ], [
            'password.different' => 'The new password must be different from your current password.',
        ]);

        $admin = $request->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            return $this->error('Current password is incorrect.', 422);
        }

        $admin->update(['password' => $request->password]);

        return $this->success(null, 'Password changed successfully.');
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::broker('admins')->sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? $this->success(null, __($status))
            : $this->error(__($status), 422);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Admin $admin, string $password) {
                $admin->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->success(null, 'Password reset successfully.')
            : $this->error(__($status), 422);
    }
}
