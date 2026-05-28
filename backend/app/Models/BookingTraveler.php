<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingTraveler extends Model
{
    protected $fillable = [
        'booking_id', 'full_name', 'nationality', 'doc_type',
        'doc_number', 'age_group', 'special_needs', 'extra_data', 'is_leader', 'order',
    ];

    protected $casts = [
        'is_leader' => 'boolean',
        'extra_data' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
