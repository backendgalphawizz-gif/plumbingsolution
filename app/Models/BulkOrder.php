<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class BulkOrder extends Model
{
    protected $fillable = [
        'reference_number', 'user_id', 'full_name', 'mobile',
        'requirement_description', 'status', 'admin_notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(BulkOrderFile::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function canReceiveQuotation(): bool
    {
        if (in_array($this->status, ['customer_approved', 'order_created'], true)) {
            return false;
        }

        return ! $this->quotations()
            ->whereIn('status', ['sent', 'approved', 'draft'])
            ->exists();
    }
}
