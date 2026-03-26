<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourSchemaMarkup extends Model
{
    protected $table = 'tour_schema_markup';
    
    protected $fillable = [
        'tour_translation_id',
        'schema_type',
        'schema_json',
        'auto_generate',
    ];

    protected $casts = [
        'schema_json' => 'array',
        'auto_generate' => 'boolean',
    ];

    public function tourTranslation(): BelongsTo
    {
        return $this->belongsTo(TourTranslation::class);
    }
}
