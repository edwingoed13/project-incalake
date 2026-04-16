<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tour extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'primary_language_id',
        'city_id',
        'city_name',
        'city_latitude',
        'city_longitude',
        'service_type',
        'status',
        'difficulty',
        'target_audience',
        'capacity',
        'cupos',
        'duration_days',
        'duration_hours',
        'departure_time',
        'departure_times',
        'departure_period',
        'duration_quantity',
        'duration_unit',
        'timezone',
        'start_time',
        'booking_anticipation_hours',
        'payment_method',
        'tax_percentage',
        'advance_payment_percentage',
        'policy_type',
        'policy_description',
        'policy_description_custom',
        'booking_anticipation_quantity',
        'booking_anticipation_unit',
        'operational_info_required',
        'personal_info_required',
        'enable_meeting_point',
        'enable_hotel_pickup',
        'meeting_point_description',
        'meeting_point_lat',
        'meeting_point_lng',
        'pickup_location_description',
        'pickup_center_lat',
        'pickup_center_lng',
        'pickup_radius_km',
        'dropoff_location_description',
        'guide_type',
        'guide_languages',
        'require_availability',
        'availability_data',
        'blocks_data',
        'offers_data',
        'data_requirement',
        'index_status',
        'follow_status',
        'featured_image_path',
        'thumbnail_path',
        'attachments_path',
        'youtube_url',
        'active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'cupos' => 'integer',
        'duration_days' => 'integer',
        'duration_hours' => 'integer',
        'booking_anticipation_hours' => 'integer',
        'tax_percentage' => 'decimal:2',
        'advance_payment_percentage' => 'integer',
        'booking_anticipation_quantity' => 'integer',
        'data_requirement' => 'integer',
        'active' => 'boolean',
        'enable_meeting_point' => 'boolean',
        'enable_hotel_pickup' => 'boolean',
        'require_availability' => 'boolean',
        'availability_data' => 'array',
        'blocks_data' => 'array',
        'offers_data' => 'array',
        'operational_info_required' => 'array',
        'personal_info_required' => 'array',
        'guide_languages' => 'array',
        'departure_times' => 'array',
    ];

    // ==================== RELATIONSHIPS ====================

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function primaryLanguage(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'primary_language_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(TourTranslation::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(TourPrice::class);
    }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(TourMediaGallery::class)->orderBy('order');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CategoryNew::class, 'tour_categories', 'tour_id', 'category_id')
            ->withTimestamps();
    }

    public function mapPoints(): HasMany
    {
        return $this->hasMany(TourMapPoint::class)->orderBy('order');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get translation for specific language
     */
    public function getTranslation(string $languageCode): ?TourTranslation
    {
        return $this->translations()
            ->whereHas('language', fn($q) => $q->where('code', strtoupper($languageCode)))
            ->first();
    }

    /**
     * Get name in specific language (fallback to first translation)
     */
    public function getName(string $languageCode = 'ES'): string
    {
        $translation = $this->getTranslation($languageCode);
        return $translation?->h1_title ?? $this->translations->first()?->h1_title ?? $this->code;
    }

    /**
     * Get slug for specific language (fallback to first translation)
     */
    public function getSlug(string $languageCode = 'ES'): ?string
    {
        $translation = $this->getTranslation($languageCode);
        return $translation?->slug ?? $this->translations->first()?->slug;
    }

    /**
     * Get minimum price (from active prices).
     * Uses the FIRST age_stage (lowest id = primary/adult stage)
     * to stay consistent with what the booking widget shows.
     */
    public function getMinPriceAttribute(): ?float
    {
        // Get the first (primary) age_stage — this is what the booking widget defaults to
        $firstStage = $this->prices()
            ->where('active', true)
            ->orderBy('age_stage_id')
            ->value('age_stage_id');

        if ($firstStage) {
            return $this->prices()
                ->where('active', true)
                ->where('age_stage_id', $firstStage)
                ->min('amount');
        }

        return $this->prices()->where('active', true)->min('amount');
    }

    /**
     * Check if tour is bookable
     */
    public function isBookable(): bool
    {
        return $this->active 
            && $this->status === 'published' 
            && ($this->cupos === null || $this->cupos > 0);
    }

    /**
     * Get full duration formatted
     */
    public function getFullDurationAttribute(): string
    {
        $parts = [];
        if ($this->duration_days > 0) {
            $parts[] = $this->duration_days . ($this->duration_days === 1 ? ' day' : ' days');
        }
        if ($this->duration_hours > 0) {
            $parts[] = $this->duration_hours . ($this->duration_hours === 1 ? ' hour' : ' hours');
        }
        return implode(' ', $parts) ?: 'N/A';
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeBookable($query)
    {
        return $query->active()
            ->published()
            ->where(function($q) {
                $q->whereNull('cupos')->orWhere('cupos', '>', 0);
            });
    }

    public function scopeByCity($query, int $cityId)
    {
        return $query->where('city_id', $cityId);
    }

    public function scopeByServiceType($query, string $type)
    {
        return $query->where('service_type', $type);
    }
}
