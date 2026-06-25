<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminValidation as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('admin.profile.edit', ['admin' => auth('admin')->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'name' => V::nameRules(),
            'mobile' => V::mobileRules(),
            'role_title' => ['nullable', 'string', V::maxRule('role_title')],
            'avatar' => V::imageRules(required: false),
        ]);

        if ($request->hasFile('avatar')) {
            if ($admin->avatar) {
                Storage::disk('public')->delete($admin->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('admins/avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $admin->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => V::loginPasswordRules(),
            'password' => array_merge(V::passwordRules(), ['confirmed', 'different:current_password']),
        ], [
            'current_password.required' => 'Please enter your current password.',
            'password.different' => 'The new password must be different from your current password.',
            'password.confirmed' => 'The new password confirmation does not match.',
        ]);

        $admin = auth('admin')->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $admin->update(['password' => $request->password]);

        return back()->with('success', 'Password changed successfully.');
    }
}
