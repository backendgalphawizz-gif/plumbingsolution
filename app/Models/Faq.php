<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'audience', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }
}
