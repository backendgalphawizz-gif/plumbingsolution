<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'user_id', 'vendor_id', 'status', 'subtotal', 'tax_amount',
        'shipping_amount', 'discount_amount', 'coupon_code', 'total_amount', 'shipping_address',
        'shipping_address_label', 'billing_address', 'notes', 'cancelled_at', 'cancellation_reason',
        'tracking_number', 'courier_name', 'invoice_path',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(OrderStatusLog::class)->latest();
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable')->latest();
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }
}
