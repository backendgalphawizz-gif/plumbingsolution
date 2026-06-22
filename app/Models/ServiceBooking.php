<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class ServiceBooking extends Model
{
    protected $fillable = [
        'booking_number', 'user_id', 'service_id', 'service_provider_id', 'service_name',
        'description', 'address', 'scheduled_at', 'rescheduled_at', 'status', 'amount', 'subtotal',
        'discount_amount', 'coupon_code', 'notes', 'cancellation_reason', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'scheduled_at' => 'datetime',
            'rescheduled_at' => 'datetime',
            'completed_at' => 'datetime',
            'amount' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingLog::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(BookingImage::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(ServiceProviderReview::class, 'service_booking_id');
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
