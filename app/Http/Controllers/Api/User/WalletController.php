<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\WalletTransactionCategory;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\WalletTransaction;
use App\Services\WalletService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    use ApiResponse;

    public function show(Request $request, WalletService $wallet): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'wallet_balance' => $wallet->balance($user),
            'currency' => 'INR',
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'category' => ['nullable', 'in:'.implode(',', array_column(WalletTransactionCategory::cases(), 'value'))],
            'direction' => ['nullable', 'in:credit,debit'],
            'search' => V::searchRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $transactions = $request->user()->walletTransactions()
            ->when($request->category, fn ($q, $category) => $q->where('category', $category))
            ->when($request->direction, fn ($q, $direction) => $q->where('direction', $direction))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'wallet_balance' => round((float) $request->user()->wallet_balance, 2),
            'items' => collect($transactions->items())
                ->map(fn (WalletTransaction $tx) => UserApiFormatter::walletTransaction($tx))
                ->values(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }
}
