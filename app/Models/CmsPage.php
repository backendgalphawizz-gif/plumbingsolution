<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $fillable = ['slug', 'audience', 'title', 'content', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeForAudience($query, string $audience)
    {
        return $query->where('audience', $audience);
    }

    public static function legalPage(string $slug, string $audience): ?self
    {
        return static::where('slug', $slug)
            ->forAudience($audience)
            ->where('is_active', true)
            ->first();
    }
}
