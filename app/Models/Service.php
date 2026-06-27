<?php

namespace App\Models;

use App\Enums\ProviderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'service_category_id', 'service_provider_id', 'name', 'slug', 'description', 'image',
        'starting_price', 'rating', 'providers_count', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starting_price' => 'decimal:2',
            'rating' => 'decimal:1',
            'status' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class)->orderBy('sort_order');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_service')
            ->withPivot(['price', 'is_available']);
    }

    public function scopeWithNearbyProvider(Builder $query, float $latitude, float $longitude, float $radiusKm = 10): Builder
    {
        return $query->whereHas('providers', function (Builder $providerQuery) use ($latitude, $longitude, $radiusKm) {
            $providerQuery
                ->where('service_providers.status', ProviderStatus::Approved)
                ->where('service_provider_service.is_available', true)
                ->withinRadius($latitude, $longitude, $radiusKm);
        });
    }
}
