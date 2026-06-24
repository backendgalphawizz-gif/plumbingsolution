<?php

namespace App\Http\Controllers\Api\User;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Services\OrderReturnService;
use App\Support\AdminValidation as V;
use App\Support\UserApiFormatter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderReturnController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $returns = $request->user()->orderReturns()
            ->with(['orderItem', 'order.vendor'])
            ->latest()
            ->paginate(15);

        return $this->success([
            'items' => collect($returns->items())->map(fn (OrderReturn $r) => UserApiFormatter::orderReturn($r)),
            'pagination' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function show(Request $request, OrderReturn $orderReturn): JsonResponse
    {
        abort_if($orderReturn->user_id !== $request->user()->id, 403);

        $orderReturn->load(['orderItem', 'order.vendor']);

        return $this->success(UserApiFormatter::orderReturn($orderReturn, detailed: true));
    }

    public function store(
        Request $request,
        Order $order,
        OrderItem $orderItem,
        OrderReturnService $returns,
    ): JsonResponse {
        abort_if($order->user_id !== $request->user()->id, 403);
        abort_if($orderItem->order_id !== $order->id, 404);

        if ($order->status !== OrderStatus::Delivered) {
            return $this->error('Returns are only allowed for delivered orders.', 422);
        }

        $data = $request->validate([
            'reason' => V::reasonRules(),
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.$orderItem->quantity],
        ]);

        $quantity = (int) ($data['quantity'] ?? $orderItem->quantity);
        $returnable = OrderReturn::returnableQuantity($orderItem);

        if ($returnable < 1) {
            return $this->error('This item already has a pending or approved return request.', 422);
        }

        if ($quantity > $returnable) {
            return $this->error("You can return at most {$returnable} unit(s) for this item.", 422);
        }

        $return = $returns->createRequest($order, $orderItem, $quantity, $data['reason']);

        return $this->success(
            UserApiFormatter::orderReturn($return, detailed: true),
            'Return request submitted.',
            201,
        );
    }
}
