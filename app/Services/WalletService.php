<?php

namespace App\Services;

use App\Enums\WalletTransactionCategory;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletService
{
    public function balance(User $user): float
    {
        return round((float) $user->wallet_balance, 2);
    }

    public function credit(
        User $user,
        float $amount,
        ?WalletTransactionCategory $category = null,
        ?string $description = null,
        ?Model $reference = null,
        ?array $metadata = null,
    ): ?WalletTransaction {
        if ($amount <= 0) {
            return null;
        }

        $amount = round($amount, 2);

        User::query()
            ->whereKey($user->id)
            ->increment('wallet_balance', $amount);

        $user->refresh();

        if ($category && $description) {
            return $this->record(
                $user,
                direction: 'credit',
                category: $category,
                amount: $amount,
                description: $description,
                reference: $reference,
                metadata: $metadata,
            );
        }

        return null;
    }

    public function debit(
        User $user,
        float $amount,
        ?WalletTransactionCategory $category = null,
        ?string $description = null,
        ?Model $reference = null,
        ?array $metadata = null,
    ): bool {
        if ($amount <= 0) {
            return true;
        }

        $amount = round($amount, 2);

        $affected = User::query()
            ->whereKey($user->id)
            ->where('wallet_balance', '>=', $amount)
            ->decrement('wallet_balance', $amount);

        if ($affected <= 0) {
            return false;
        }

        $user->refresh();

        if ($category && $description) {
            $this->record(
                $user,
                direction: 'debit',
                category: $category,
                amount: $amount,
                description: $description,
                reference: $reference,
                metadata: $metadata,
            );
        }

        return true;
    }

    public function refund(
        User $user,
        float $amount,
        ?WalletTransactionCategory $category = null,
        ?string $description = null,
        ?Model $reference = null,
        ?array $metadata = null,
    ): ?WalletTransaction {
        return $this->credit($user, $amount, $category, $description, $reference, $metadata);
    }

    private function record(
        User $user,
        string $direction,
        WalletTransactionCategory $category,
        float $amount,
        string $description,
        ?Model $reference = null,
        ?array $metadata = null,
    ): WalletTransaction {
        return WalletTransaction::create([
            'user_id' => $user->id,
            'transaction_id' => 'WTX-'.strtoupper(Str::random(10)),
            'direction' => $direction,
            'category' => $category,
            'amount' => $amount,
            'balance_after' => $this->balance($user),
            'description' => $description,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
