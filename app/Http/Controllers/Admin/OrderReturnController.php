<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderReturnStatus;
use App\Http\Controllers\Controller;
use App\Models\OrderReturn;
use App\Services\OrderReturnService;
use App\Support\AdminValidation as V;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderReturnController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'search' => V::searchRules(),
        ]);

        $returns = $this->filteredReturns($request)->paginate(15)->withQueryString();

        return view('admin.order-returns.index', [
            'returns' => $returns,
            'pendingCount' => OrderReturn::where('status', OrderReturnStatus::Pending)->count(),
        ]);
    }

    public function show(OrderReturn $orderReturn): View
    {
        $orderReturn->load([
            'order.user',
            'order.vendor',
            'orderItem.product',
            'reviewer',
        ]);

        return view('admin.order-returns.show', compact('orderReturn'));
    }

    public function approve(Request $request, OrderReturn $orderReturn, OrderReturnService $returns): RedirectResponse
    {
        if ($orderReturn->status !== OrderReturnStatus::Pending) {
            return back()->with('error', 'Only pending return requests can be approved.');
        }

        $data = $request->validate(['notes' => V::notesRules()]);

        try {
            $returns->approve($orderReturn, auth('admin')->user(), $data['notes'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.order-returns.show', $orderReturn)
            ->with('success', 'Return request approved.');
    }

    public function reject(Request $request, OrderReturn $orderReturn, OrderReturnService $returns): RedirectResponse
    {
        if ($orderReturn->status !== OrderReturnStatus::Pending) {
            return back()->with('error', 'Only pending return requests can be rejected.');
        }

        $data = $request->validate(['reason' => V::reasonRules()]);

        try {
            $returns->reject($orderReturn, $data['reason'], auth('admin')->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.order-returns.show', $orderReturn)
            ->with('success', 'Return request rejected.');
    }

    private function filteredReturns(Request $request): Builder
    {
        return OrderReturn::query()
            ->with(['order', 'orderItem', 'user', 'vendor'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($q) use ($search) {
                    $q->where('return_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($uq) => $uq
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%"))
                        ->orWhereHas('orderItem', fn ($iq) => $iq->where('product_name', 'like', "%{$search}%"));
                });
            })
            ->latest();
    }
}
