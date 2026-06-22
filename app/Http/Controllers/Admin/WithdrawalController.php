<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\ProviderWithdrawal;
use App\Models\UserWithdrawal;
use App\Models\VendorWithdrawal;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $type = $this->resolveType($request);
        $withdrawals = $this->filteredWithdrawals($type, $request)->paginate(15)->withQueryString();

        return view('admin.withdrawals.index', [
            'type' => $type,
            'withdrawals' => $withdrawals,
            'counts' => [
                'vendor' => VendorWithdrawal::where('status', WithdrawalStatus::Pending)->count(),
                'provider' => ProviderWithdrawal::where('status', WithdrawalStatus::Pending)->count(),
                'user' => UserWithdrawal::where('status', WithdrawalStatus::Pending)->count(),
            ],
        ]);
    }

    public function show(Request $request, string $type, int $withdrawal): View
    {
        $type = $this->resolveType($request->merge(['type' => $type]));
        $model = $this->findWithdrawal($type, $withdrawal);

        return view('admin.withdrawals.show', [
            'type' => $type,
            'withdrawal' => $model,
        ]);
    }

    public function approve(Request $request, string $type, int $withdrawal): RedirectResponse
    {
        $type = $this->resolveType($request->merge(['type' => $type]));
        $model = $this->findWithdrawal($type, $withdrawal);

        if ($model->status !== WithdrawalStatus::Pending) {
            return back()->with('error', 'Only pending requests can be approved.');
        }

        $data = $request->validate(['notes' => V::notesRules()]);

        $model->update([
            'status' => WithdrawalStatus::Paid,
            'processed_at' => now(),
            'notes' => $data['notes'] ?? $model->notes,
        ]);

        return redirect()
            ->route('admin.withdrawals.show', ['type' => $type, 'withdrawal' => $model->id])
            ->with('success', 'Withdrawal marked as paid.');
    }

    public function reject(Request $request, string $type, int $withdrawal): RedirectResponse
    {
        $type = $this->resolveType($request->merge(['type' => $type]));
        $model = $this->findWithdrawal($type, $withdrawal);

        if ($model->status !== WithdrawalStatus::Pending) {
            return back()->with('error', 'Only pending requests can be rejected.');
        }

        $data = $request->validate(['reason' => V::reasonRules()]);

        $model->update([
            'status' => WithdrawalStatus::Rejected,
            'processed_at' => now(),
            'notes' => $data['reason'],
        ]);

        return redirect()
            ->route('admin.withdrawals.show', ['type' => $type, 'withdrawal' => $model->id])
            ->with('success', 'Withdrawal rejected.');
    }

    private function resolveType(Request $request): string
    {
        $request->validate(['type' => ['nullable', 'in:vendor,provider,user']]);

        return $request->get('type', 'vendor');
    }

    private function filteredWithdrawals(string $type, Request $request): Builder
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,paid,rejected'],
            'search' => V::searchRules(),
        ]);

        $request->validate(V::dateRangeRules());

        $query = match ($type) {
            'provider' => ProviderWithdrawal::query()->with('serviceProvider'),
            'user' => UserWithdrawal::query()->with('user'),
            default => VendorWithdrawal::query()->with('vendor'),
        };

        return $this->applyDateRange(
            $query
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, function ($q, $search) use ($type) {
                    $q->where(function ($q) use ($search, $type) {
                        $q->where('transaction_id', 'like', "%{$search}%");

                        match ($type) {
                            'provider' => $q->orWhereHas('serviceProvider', fn ($rq) => $rq
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%")),
                            'user' => $q->orWhereHas('user', fn ($rq) => $rq
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%")),
                            default => $q->orWhereHas('vendor', fn ($rq) => $rq
                                ->where('shop_name', 'like', "%{$search}%")
                                ->orWhere('owner_name', 'like', "%{$search}%")
                                ->orWhere('mobile', 'like', "%{$search}%")),
                        };
                    });
                })
                ->latest(),
            $request
        );
    }

    private function findWithdrawal(string $type, int $id): VendorWithdrawal|ProviderWithdrawal|UserWithdrawal
    {
        return match ($type) {
            'provider' => ProviderWithdrawal::with('serviceProvider')->findOrFail($id),
            'user' => UserWithdrawal::with('user')->findOrFail($id),
            default => VendorWithdrawal::with('vendor')->findOrFail($id),
        };
    }
}
