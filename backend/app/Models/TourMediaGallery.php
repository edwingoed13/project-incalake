<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourMediaGallery extends Model
{
    protected $table = 'tour_media_gallery';
    
    protected $fillable = [
        'tour_id',
        'language_id',
        'image_path',
        'original_path',
        'crop_data',
        'alt_text',
        'title_text',
        'description',
        // is_primary was missing here since day one — every mass assignment
        // (wizard Step 5 included) silently dropped it, so no tour ever got a
        // featured image persisted through this model.
        'is_primary',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_primary' => 'boolean',
        'crop_data' => 'array',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
