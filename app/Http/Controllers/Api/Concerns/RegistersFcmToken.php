<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;

trait RegistersFcmToken
{
    protected function fcmTokenRules(): array
    {
        return [
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function saveFcmToken(?User $user, ?string $token): void
    {
        if ($user && filled($token)) {
            $user->update(['fcm_token' => $token]);
        }
    }

    protected function clearFcmToken(User $user): void
    {
        $user->update(['fcm_token' => null]);
    }
}
