<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminValidation as V;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
        ]);

        $admin->update($data);

        return back()->with('success', 'Profile updated.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', V::maxRule('password')],
            'password' => array_merge(V::passwordRules(), ['confirmed']),
        ]);

        $admin = auth('admin')->user();

        if (! Hash::check($request->current_password, $admin->password)) {
            return back()->with('error', 'Current password is incorrect.');
        }

        $admin->update(['password' => $request->password]);

        return back()->with('success', 'Password changed.');
    }
}
