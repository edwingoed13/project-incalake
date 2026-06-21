<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityInquiry extends Model
{
    protected $fillable = [
        'tour_id', 'tour_title', 'name', 'email', 'phone',
        'preferred_date', 'adults', 'children', 'message', 'language', 'status',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }
}
