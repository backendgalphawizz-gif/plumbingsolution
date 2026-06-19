<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Admin\Concerns\ExportsAdminTable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\AdminValidation as V;

class OrderController extends Controller
{
    use ExportsAdminTable;

    public function index(Request $request): View
    {
        $orders = $this->filteredOrders($request)->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function export(Request $request)
    {
        $orders = $this->filteredOrders($request)->get();

        return $this->exportResponse(
            $request,
            'orders',
            'Order List',
            ['Order #', 'Customer', 'Vendor', 'Total', 'Status', 'Created Date'],
            $orders->map(fn (Order $o) => [
                $o->order_number,
                $o->user?->name ?? '',
                $o->vendor?->shop_name ?? '',
                number_format((float) $o->total_amount, 2),
                $o->status->value ?? $o->status,
                $o->created_at->format('M d, Y'),
            ])
        );
    }

    private function filteredOrders(Request $request): Builder
    {
        return $this->applyDateRange(
            Order::with(['user', 'vendor'])
                ->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
                ->latest(),
            $request
        );
    }

    public function show(Order $order): View
    {
        $order->load(['user', 'vendor', 'items', 'statusLogs']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))],
            'notes' => V::notesRules(),
        ]);

        $order->update(['status' => $request->status]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $request->status,
            'notes' => $request->notes,
            'changed_by' => auth('admin')->id(),
        ]);

        return back()->with('success', 'Order status updated.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $request->validate(['reason' => V::reasonRules()]);

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        return back()->with('success', 'Order cancelled.');
    }
}
