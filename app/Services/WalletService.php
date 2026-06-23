<?php

namespace App\Services;

use App\Models\User;

class WalletService
{
    public function balance(User $user): float
    {
        return round((float) $user->wallet_balance, 2);
    }

    public function credit(User $user, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        User::query()
            ->whereKey($user->id)
            ->increment('wallet_balance', round($amount, 2));

        $user->refresh();
    }

    public function debit(User $user, float $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        $amount = round($amount, 2);

        $affected = User::query()
            ->whereKey($user->id)
            ->where('wallet_balance', '>=', $amount)
            ->decrement('wallet_balance', $amount);

        if ($affected > 0) {
            $user->refresh();
        }

        return $affected > 0;
    }

    public function refund(User $user, float $amount): void
    {
        $this->credit($user, $amount);
    }
}
