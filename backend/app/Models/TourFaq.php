<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourFaq extends Model
{
    protected $fillable = [
        'tour_translation_id',
        'question',
        'answer',
        'order',
        'active',
    ];

    protected $casts = [
        'order' => 'integer',
        'active' => 'boolean',
    ];

    public function tourTranslation(): BelongsTo
    {
        return $this->belongsTo(TourTranslation::class);
    }
}
