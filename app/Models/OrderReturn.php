<?php

namespace App\Models;

use App\Enums\OrderReturnStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends Model
{
    protected $fillable = [
        'return_number', 'order_id', 'order_item_id', 'user_id', 'vendor_id',
        'quantity', 'refund_amount', 'reason', 'status', 'admin_notes',
        'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderReturnStatus::class,
            'refund_amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }

    public static function reservedQuantityForItem(OrderItem $item): int
    {
        return (int) static::query()
            ->where('order_item_id', $item->id)
            ->whereIn('status', [OrderReturnStatus::Pending, OrderReturnStatus::Approved])
            ->sum('quantity');
    }

    public static function returnableQuantity(OrderItem $item): int
    {
        return max(0, $item->quantity - static::reservedQuantityForItem($item));
    }
}
