<?php

namespace App\Http\Controllers\Api\Provider;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Api\Provider\Concerns\ResolvesProvider;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\ProviderWithdrawal;
use App\Services\ProviderEarningsService;
use App\Support\AdminValidation as V;
use App\Support\ProviderApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EarningsController extends Controller
{
    use ApiResponse, ResolvesProvider;

    public function index(Request $request, ProviderEarningsService $earnings): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        $request->validate([
            'search' => V::searchRules(),
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $totalEarnings = $earnings->totalEarnings($provider);
        $walletAmount = $earnings->walletAmount($provider);

        $payments = $earnings->transactionsQuery($provider)
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('payment_id', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->paginate($request->integer('per_page', 15));

        $withdrawals = $provider->withdrawals()
            ->when($request->search, fn ($q, $s) => $q->where('transaction_id', 'like', "%{$s}%"))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($withdrawal) => ProviderApiFormatter::withdrawal($withdrawal));

        return $this->success([
            'summary' => ProviderApiFormatter::earningsSummary($provider, $totalEarnings, $walletAmount),
            'transactions' => collect($payments->items())
                ->map(fn ($payment) => ProviderApiFormatter::transaction($payment))
                ->values(),
            'withdrawals' => $withdrawals,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function withdraw(Request $request, ProviderEarningsService $earnings): JsonResponse
    {
        $provider = $this->requireProvider($request);
        if ($provider instanceof JsonResponse) {
            return $provider;
        }

        if ($response = $this->ensureApproved($provider)) {
            return $response;
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $walletAmount = $earnings->walletAmount($provider);

        if ($data['amount'] > $walletAmount) {
            return $this->error('Insufficient wallet balance.', 422);
        }

        if (! $provider->bank_name || ! $provider->account_number) {
            return $this->error('Bank details are required before withdrawal.', 422);
        }

        $withdrawal = ProviderWithdrawal::create([
            'service_provider_id' => $provider->id,
            'transaction_id' => 'TXN-'.strtoupper(Str::random(8)),
            'amount' => $data['amount'],
            'status' => WithdrawalStatus::Pending,
            'bank_name' => $provider->bank_name,
            'account_number' => $provider->account_number,
            'ifsc_code' => $provider->ifsc_code,
        ]);

        return $this->success([
            'withdrawal' => ProviderApiFormatter::withdrawal($withdrawal),
            'wallet_amount' => $earnings->walletAmount($provider->fresh()),
        ], 'Withdrawal request submitted.');
    }
}
