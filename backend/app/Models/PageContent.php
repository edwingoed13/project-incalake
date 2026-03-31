<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageContent extends Model
{
    protected $fillable = [
        'page',
        'language_id',
        'content',
        'published',
    ];

    protected $casts = [
        'content' => 'array',
        'published' => 'boolean',
    ];

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }
}
