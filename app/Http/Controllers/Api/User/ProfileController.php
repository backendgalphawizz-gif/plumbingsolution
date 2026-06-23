<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->success(UserApiFormatter::user($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->filled('mobile')) {
            $request->merge(['mobile' => trim((string) $request->input('mobile'))]);
        }
        if ($request->has('email')) {
            $request->merge(['email' => $request->filled('email') ? trim((string) $request->input('email')) : null]);
        }

        $data = $request->validate([
            'name' => V::nameRules(),
            'email' => [
                'nullable',
                'string',
                'email',
                V::maxRule('email'),
                Rule::unique('users', 'email')->ignore($user),
            ],
            'mobile' => array_merge(V::mobileRules(), [Rule::unique('users', 'mobile')->ignore($user)]),
            'address' => V::addressRules(),
            'avatar' => ['nullable', 'image', 'max:2048'],
            'profile_image' => ['nullable', 'image', 'max:2048'],
        ]);

        unset($data['avatar'], $data['profile_image']);

        if ($request->hasFile('avatar') || $request->hasFile('profile_image')) {
            $file = $request->file('avatar') ?? $request->file('profile_image');

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $file->store('avatars', 'public');
        }

        $user->update($data);

        return $this->success(UserApiFormatter::user($user->fresh()), 'Profile updated.');
    }
}
