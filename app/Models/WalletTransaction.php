<?php

namespace App\Models;

use App\Enums\WalletTransactionCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id', 'transaction_id', 'direction', 'category', 'amount',
        'balance_after', 'description', 'reference_type', 'reference_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'category' => WalletTransactionCategory::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
