<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $filter = $request->get('status', 'all');

        $orders = $request->user()->orders()
            ->with(['items', 'vendor'])
            ->when($filter !== 'all', function ($q) use ($filter) {
                $map = [
                    'processing' => [OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed],
                    'out_for_delivery' => [OrderStatus::Shipped],
                    'delivered' => [OrderStatus::Delivered],
                    'cancelled' => [OrderStatus::Cancelled],
                ];
                if (isset($map[$filter])) {
                    $q->whereIn('status', array_map(fn ($s) => $s->value, $map[$filter]));
                }
            })
            ->latest()
            ->paginate(15);

        return $this->success([
            'items' => collect($orders->items())->map(fn ($o) => UserApiFormatter::order($o)),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        $order->load(['items', 'vendor']);

        return $this->success(UserApiFormatter::order($order, detailed: true));
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        abort_if($order->user_id !== $request->user()->id, 403);

        if (! in_array($order->status, [OrderStatus::Pending, OrderStatus::Accepted, OrderStatus::Packed])) {
            return $this->error('This order cannot be cancelled.', 422);
        }

        $data = $request->validate(['reason' => V::reasonRules()]);

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $data['reason'],
        ]);

        return $this->success(UserApiFormatter::order($order->fresh()->load('items')), 'Order cancelled.');
    }
}
