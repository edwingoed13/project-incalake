<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'tour_id', 'name', 'review_date', 'rating', 'title',
        'comment', 'language', 'opinion', 'published', 'featured',
    ];

    protected $casts = [
        'published' => 'boolean',
        'featured' => 'boolean',
        'rating' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
