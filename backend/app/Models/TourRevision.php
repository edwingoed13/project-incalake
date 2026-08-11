<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pending (unpublished) edits for a tour. See the create_tour_revisions_table
 * migration for why the payload is wizard-shaped rather than API-shaped.
 */
class TourRevision extends Model
{
    protected $fillable = [
        'tour_id',
        'payload',
        'schema_version',
        'version',
        'updated_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'version' => 'integer',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
