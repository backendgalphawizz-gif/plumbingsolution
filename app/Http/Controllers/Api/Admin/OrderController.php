<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\Refund;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['user:id,name,email', 'vendor:id,shop_name'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->where('order_number', 'like', "%{$s}%"))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return $this->success($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return $this->success($order->load(['user', 'vendor', 'items.product', 'statusLogs.changedBy']));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(OrderStatus::cases(), 'value'))],
            'notes' => ['nullable', 'string'],
        ]);

        $nextStatus = OrderStatus::from($request->status);

        if (! $order->status->canTransitionTo($nextStatus)) {
            return $this->error(
                'Order cannot move from '.$order->status->label().' to '.$nextStatus->label().'. Follow the next step in the delivery flow.',
                422,
            );
        }

        $order->update(['status' => $nextStatus]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $nextStatus->value,
            'notes' => $request->notes,
            'changed_by' => $request->user()->id,
        ]);

        return $this->success($order->fresh()->load('statusLogs'), 'Order status updated.');
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if (! $order->status->canCancel()) {
            return $this->error('This order cannot be cancelled in its current status.', 422);
        }

        $request->validate(['reason' => ['required', 'string']]);

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $request->reason,
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
            'notes' => $request->reason,
            'changed_by' => $request->user()->id,
        ]);

        return $this->success($order->fresh(), 'Order cancelled.');
    }

    public function refund(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
        ]);

        if (! $order->status->canTransitionTo(OrderStatus::Refunded)) {
            return $this->error('Refund is only allowed after the order has been returned.', 422);
        }

        $refund = Refund::create([
            'payment_id' => $request->payment_id,
            'refund_id' => 'REF-'.Str::upper(Str::random(10)),
            'amount' => $request->amount,
            'status' => 'pending',
            'reason' => $request->reason,
            'processed_by' => $request->user()->id,
        ]);

        if (! $order->status->canTransitionTo(OrderStatus::Refunded)) {
            return $this->error('Refund is only allowed after the order has been returned.', 422);
        }

        $order->update(['status' => OrderStatus::Refunded]);

        return $this->success($refund, 'Refund initiated.');
    }
}
