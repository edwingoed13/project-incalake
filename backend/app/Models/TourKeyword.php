<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourKeyword extends Model
{
    protected $fillable = ['tour_translation_id', 'keyword', 'is_primary'];
    protected $casts = ['is_primary' => 'boolean'];

    public function tourTranslation(): BelongsTo
    {
        return $this->belongsTo(TourTranslation::class);
    }
}
