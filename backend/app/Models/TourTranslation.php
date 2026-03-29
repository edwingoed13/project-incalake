<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TourTranslation extends Model
{
    protected $fillable = [
        'tour_id',
        'language_id',
        'slug',
        'h1_title',
        'meta_title',
        'meta_description',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image_path',
        'twitter_title',
        'twitter_description',
        'twitter_image_path',
        'short_description',
        'long_description',
        'what_includes',
        'what_not_includes',
        'itinerary',
        'recommendations',
        'what_to_bring',
        'policies',
        'cancellation_policy',
        'cta_text',
        'price_from_label',
        'ads_headline',
        'ads_description',
        'youtube_url',
        'media_texts',
        'booking_texts',
    ];

    protected $casts = [
        'what_includes' => 'array',
        'what_not_includes' => 'array',
        'itinerary' => 'array',
        'recommendations' => 'array',
        'what_to_bring' => 'array',
        'policies' => 'array',
        'media_texts' => 'array',
        'booking_texts' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(TourKeyword::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(TourFaq::class)->where('active', true)->orderBy('order');
    }

    public function schemaMarkup(): HasMany
    {
        return $this->hasMany(TourSchemaMarkup::class);
    }

    public function utmCampaigns(): HasMany
    {
        return $this->hasMany(TourUtmCampaign::class)->where('active', true);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get primary keyword
     */
    public function getPrimaryKeyword(): ?string
    {
        return $this->keywords()->where('is_primary', true)->first()?->keyword;
    }

    /**
     * Get all secondary keywords
     */
    public function getSecondaryKeywords(): array
    {
        return $this->keywords()->where('is_primary', false)->pluck('keyword')->toArray();
    }

    /**
     * Get meta title length (for SEO validation)
     */
    public function getMetaTitleLengthAttribute(): int
    {
        return mb_strlen($this->meta_title ?? '');
    }

    /**
     * Get meta description length (for SEO validation)
     */
    public function getMetaDescriptionLengthAttribute(): int
    {
        return mb_strlen($this->meta_description ?? '');
    }

    /**
     * Check if SEO is complete
     */
    public function isSeoComplete(): bool
    {
        return !empty($this->meta_title) 
            && !empty($this->meta_description)
            && !empty($this->slug)
            && $this->keywords()->where('is_primary', true)->exists();
    }

    /**
     * Generate Schema.org JSON-LD for TouristTrip
     */
    public function generateTouristTripSchema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'TouristTrip',
            'name' => $this->h1_title,
            'description' => $this->short_description,
            'url' => url($this->slug),
            'touristType' => $this->tour->target_audience,
            'itinerary' => $this->itinerary,
        ];
    }

    /**
     * Get full URL for this tour translation
     */
    public function getUrlAttribute(): string
    {
        return url('/' . $this->language->code . '/' . $this->slug);
    }
}
