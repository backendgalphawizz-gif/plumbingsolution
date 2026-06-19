<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        $user->update($data);

        return $this->success(UserApiFormatter::user($user->fresh()), 'Profile updated.');
    }
}
