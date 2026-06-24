<?php

namespace App\Services;

use App\Enums\OrderReturnStatus;
use App\Enums\OrderStatus;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\OrderStatusLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderReturnService
{
    public function __construct(private PushNotificationService $notifications) {}

    public function createRequest(Order $order, OrderItem $item, int $quantity, string $reason): OrderReturn
    {
        $refundAmount = round(((float) $item->total_price / $item->quantity) * $quantity, 2);

        $return = OrderReturn::create([
            'return_number' => 'RET-'.strtoupper(Str::random(8)),
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'user_id' => $order->user_id,
            'vendor_id' => $order->vendor_id,
            'quantity' => $quantity,
            'refund_amount' => $refundAmount,
            'reason' => $reason,
            'status' => OrderReturnStatus::Pending,
        ]);

        $return->load(['orderItem.product', 'order', 'user', 'vendor.user']);
        $this->notifications->orderReturnRequested($return);

        return $return;
    }

    public function approve(OrderReturn $return, ?Admin $admin = null, ?string $notes = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $admin, $notes) {
            $return = OrderReturn::query()->lockForUpdate()->findOrFail($return->id);

            if ($return->status !== OrderReturnStatus::Pending) {
                throw new \RuntimeException('Only pending return requests can be approved.');
            }

            $return->update([
                'status' => OrderReturnStatus::Approved,
                'admin_notes' => $notes,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
            ]);

            $item = $return->orderItem()->with('product')->first();
            if ($item?->product) {
                $item->product->increment('stock', $return->quantity);
            }

            $order = $return->order()->first();
            if ($order && $order->status === OrderStatus::Delivered) {
                $order->update(['status' => OrderStatus::Returned]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => OrderStatus::Returned->value,
                    'notes' => "Return {$return->return_number} approved.",
                    'changed_by' => $admin?->id,
                ]);
            }

            $return->load(['orderItem', 'order.user', 'order.vendor.user', 'user']);
            $this->notifications->orderReturnReviewed($return, approved: true);

            return $return;
        });
    }

    public function reject(OrderReturn $return, string $reason, ?Admin $admin = null): OrderReturn
    {
        return DB::transaction(function () use ($return, $reason, $admin) {
            $return = OrderReturn::query()->lockForUpdate()->findOrFail($return->id);

            if ($return->status !== OrderReturnStatus::Pending) {
                throw new \RuntimeException('Only pending return requests can be rejected.');
            }

            $return->update([
                'status' => OrderReturnStatus::Rejected,
                'admin_notes' => $reason,
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now(),
            ]);

            $return->load(['orderItem', 'order.user', 'order.vendor.user', 'user']);
            $this->notifications->orderReturnReviewed($return, approved: false);

            return $return;
        });
    }
}
