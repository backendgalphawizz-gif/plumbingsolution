<?php

namespace App\Models;

use App\Enums\ProviderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    protected $fillable = [
        'user_id', 'name', 'mobile', 'email', 'avatar', 'skills', 'experience_years',
        'service_area', 'latitude', 'longitude', 'status', 'rejection_reason', 'approved_at',
        'account_number', 'account_holder_name', 'ifsc_code', 'bank_name', 'account_type',
    ];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'status' => ProviderStatus::class,
            'approved_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
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

    public function scopeWithinRadius(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $haversine = self::haversineDistanceSql();

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    public function scopeNearby(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        $haversine = self::haversineDistanceSql();

        return $query
            ->withinRadius($latitude, $longitude, $radiusKm)
            ->selectRaw("service_providers.*, {$haversine} as distance_km", [$latitude, $longitude, $latitude])
            ->orderBy('distance_km');
    }

    private static function haversineDistanceSql(): string
    {
        return '(6371 * acos(LEAST(1, GREATEST(-1, cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))))';
    }
}
