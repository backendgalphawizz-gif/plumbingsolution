<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkOrderFile extends Model
{
    protected $fillable = ['bulk_order_id', 'file_path', 'file_type', 'original_name'];

    public function bulkOrder(): BelongsTo
    {
        return $this->belongsTo(BulkOrder::class);
    }
}
