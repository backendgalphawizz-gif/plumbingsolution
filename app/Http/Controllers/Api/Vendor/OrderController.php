<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\Vendor\Concerns\ResolvesVendor;
use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\OrderStatusLog;
use App\Support\AdminValidation as V;
use App\Support\VendorApiFormatter;
use App\Services\PushNotificationService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponse, ResolvesVendor;

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $request->validate([
            'status' => ['nullable', 'in:all,new,accepted,out_for_delivery,delivered,cancelled,returned'],
            'search' => V::searchRules(),
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $orders = $vendor->orders()
            ->with(['user', 'items.product.images', 'payment'])
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$s}%"));
            }))
            ->when($request->status && $request->status !== 'all', function ($q) use ($request) {
                match ($request->status) {
                    'new' => $q->where('status', OrderStatus::Pending),
                    'accepted' => $q->whereIn('status', [OrderStatus::Accepted, OrderStatus::Packed]),
                    'out_for_delivery' => $q->where('status', OrderStatus::Shipped),
                    'delivered' => $q->where('status', OrderStatus::Delivered),
                    'cancelled' => $q->where('status', OrderStatus::Cancelled),
                    'returned' => $q->whereIn('status', [OrderStatus::Returned, OrderStatus::Refunded]),
                    default => null,
                };
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success([
            'items' => collect($orders->items())->map(fn ($order) => VendorApiFormatter::order($order))->values(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function show(Request $request, int $order): JsonResponse
    {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        $orderModel = $this->vendorOrder($vendor, $order);

        if (! $orderModel) {
            return $this->error('Order not found.', 404);
        }

        return $this->success(VendorApiFormatter::order($orderModel, detailed: true));
    }

    public function accept(Request $request, int $order): JsonResponse
    {
        return $this->transitionStatus(
            $request,
            $order,
            from: [OrderStatus::Pending],
            to: OrderStatus::Accepted,
            notes: 'Order accepted by vendor.',
            message: 'Order accepted.',
        );
    }

    public function reject(Request $request, int $order): JsonResponse
    {
        $request->validate(['reason' => V::reasonRules(required: false)]);

        return $this->transitionStatus(
            $request,
            $order,
            from: [OrderStatus::Pending],
            to: OrderStatus::Cancelled,
            notes: $request->input('reason', 'Rejected by vendor.'),
            message: 'Order rejected.',
            cancelled: true,
        );
    }

    public function outForDelivery(Request $request, int $order): JsonResponse
    {
        return $this->transitionStatus(
            $request,
            $order,
            from: [OrderStatus::Accepted, OrderStatus::Packed],
            to: OrderStatus::Shipped,
            notes: 'Order marked out for delivery.',
            message: 'Order is out for delivery.',
        );
    }

    public function deliver(Request $request, int $order): JsonResponse
    {
        return $this->transitionStatus(
            $request,
            $order,
            from: [OrderStatus::Shipped],
            to: OrderStatus::Delivered,
            notes: 'Order delivered.',
            message: 'Order marked as delivered.',
        );
    }

    private function transitionStatus(
        Request $request,
        int $orderId,
        array $from,
        OrderStatus $to,
        string $notes,
        string $message,
        bool $cancelled = false,
    ): JsonResponse {
        $vendor = $this->requireVendor($request);
        if ($vendor instanceof JsonResponse) {
            return $vendor;
        }

        if ($response = $this->ensureApproved($vendor)) {
            return $response;
        }

        $order = $this->vendorOrder($vendor, $orderId);

        if (! $order) {
            return $this->error('Order not found.', 404);
        }

        if (! in_array($order->status, $from, true)) {
            return $this->error('This order cannot be updated in its current status.', 422);
        }

        $order->update([
            'status' => $to,
            'cancelled_at' => $cancelled ? now() : null,
            'cancellation_reason' => $cancelled ? $notes : null,
        ]);

        OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $to->value,
            'notes' => $notes,
        ]);

        if ($to === OrderStatus::Delivered) {
            $vendor->loadMissing('user');
            if ($vendor->user && $order->total_amount > 0) {
                app(WalletService::class)->credit($vendor->user, (float) $order->total_amount);
            }
        }

        $order->load(['user', 'vendor.user']);
        app(PushNotificationService::class)->orderStatusUpdated(
            $order,
            str_replace('_', ' ', $to->value),
            $notes,
        );

        $order->load(['user', 'items.product.images', 'payment']);

        return $this->success(
            VendorApiFormatter::order($order, detailed: true),
            $message,
        );
    }
}
