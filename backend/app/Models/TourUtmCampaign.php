<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourUtmCampaign extends Model
{
    protected $fillable = [
        'tour_translation_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'campaign_label',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function tourTranslation(): BelongsTo
    {
        return $this->belongsTo(TourTranslation::class);
    }

    public function getFullUtmUrlAttribute(): string
    {
        $params = array_filter([
            'utm_source' => $this->utm_source,
            'utm_medium' => $this->utm_medium,
            'utm_campaign' => $this->utm_campaign,
            'utm_term' => $this->utm_term,
            'utm_content' => $this->utm_content,
        ]);
        
        return $this->tourTranslation->url . '?' . http_build_query($params);
    }
}
