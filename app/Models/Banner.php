<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title', 'image', 'redirect_type', 'redirect_id', 'redirect_url', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }
}
