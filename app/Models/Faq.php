<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = ['question', 'answer', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['status' => 'boolean'];
    }
}
