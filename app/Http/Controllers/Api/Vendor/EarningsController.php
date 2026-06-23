<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\VendorWithdrawal;
use App\Services\VendorEarningsService;
use App\Services\WalletService;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EarningsController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function index(Request $request, VendorEarningsService $earnings): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $request->validate([
            'search' => V::searchRules(),
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $totalEarnings = $earnings->totalEarnings($vendor);
        $walletAmount = $earnings->walletAmount($vendor);

        $payments = $earnings->transactionsQuery($vendor)
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('payment_id', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->paginate($request->integer('per_page', 15));

        $withdrawals = $vendor->withdrawals()
            ->when($request->search, fn ($q, $s) => $q->where('transaction_id', 'like', "%{$s}%"))
            ->when($request->from_date, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($withdrawal) => VendorApiFormatter::withdrawal($withdrawal));

        return $this->success([
            'summary' => VendorApiFormatter::earningsSummary($vendor, $totalEarnings, $walletAmount),
            'transactions' => collect($payments->items())
                ->map(fn ($payment) => VendorApiFormatter::transaction($payment))
                ->values(),
            'withdrawals' => $withdrawals,
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    public function withdraw(Request $request, VendorEarningsService $earnings, WalletService $wallet): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($response = $this->ensureApproved($vendor)) {
            return $response;
        }

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $vendor->loadMissing('user');

        if (! $vendor->user || ! $wallet->debit($vendor->user, (float) $data['amount'])) {
            return $this->error('Insufficient wallet balance.', 422);
        }

        if (! $vendor->bank_name || ! $vendor->account_number) {
            $wallet->refund($vendor->user, (float) $data['amount']);

            return $this->error('Bank details are required before withdrawal.', 422);
        }

        $withdrawal = VendorWithdrawal::create([
            'vendor_id' => $vendor->id,
            'transaction_id' => 'TXN-'.strtoupper(Str::random(8)),
            'amount' => $data['amount'],
            'status' => WithdrawalStatus::Pending,
            'bank_name' => $vendor->bank_name,
            'account_number' => $vendor->account_number,
            'ifsc_code' => $vendor->ifsc_code,
        ]);

        return $this->success([
            'withdrawal' => VendorApiFormatter::withdrawal($withdrawal),
            'wallet_amount' => $earnings->walletAmount($vendor->fresh()),
        ], 'Withdrawal request submitted.');
    }
}
