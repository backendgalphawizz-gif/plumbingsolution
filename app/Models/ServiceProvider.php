<?php

namespace App\Models;

use App\Enums\ProviderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    protected $fillable = [
        'user_id', 'name', 'mobile', 'avatar', 'skills', 'experience_years',
        'service_area', 'status', 'rejection_reason', 'approved_at',
        'account_number', 'account_holder_name', 'ifsc_code', 'bank_name', 'account_type',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'status' => ProviderStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProviderDocument::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceProviderImage::class)->orderBy('sort_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_provider_service')
            ->withPivot(['price', 'is_available']);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(ProviderWithdrawal::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ServiceProviderReview::class)->where('status', true);
    }
}
