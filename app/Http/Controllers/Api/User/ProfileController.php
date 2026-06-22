<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        $data = $request->validate([
            'name' => V::nameRules(),
            'email' => V::emailRules(required: false, uniqueTable: 'users', ignoreId: $user->id),
            'mobile' => array_merge(V::mobileRules(), ['unique:users,mobile,'.$user->id]),
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
