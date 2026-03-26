<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourPrice extends Model
{
    protected $fillable = [
        'tour_id',
        'age_stage_id',
        'nationality_id',
        'price_type',
        'amount',
        'min_quantity',
        'max_quantity',
        'active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'active' => 'boolean',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function ageStage(): BelongsTo
    {
        return $this->belongsTo(AgeStage::class);
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
