<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $language = $request->language ?? 'ES';
        $translation = $this->getTranslation($language);

        return [
            'id' => $this->id,
            'code' => $this->code,

            // Flattened fields for frontend compatibility (with fallback to any translation)
            'title' => $translation?->h1_title ?? $this->translations->first()?->h1_title ?? '',
            'slug' => $translation?->slug ?? $this->translations->first()?->slug ?? '',
            'meta_title' => $translation?->meta_title ?? $this->translations->first()?->meta_title ?? '',
            'meta_description' => $translation?->meta_description ?? $this->translations->first()?->meta_description ?? '',
            'short_description' => $translation?->short_description ?? $this->translations->first()?->short_description ?? '',
            'long_description' => $translation?->long_description ?? $this->translations->first()?->long_description ?? '',
            'itinerary' => $translation?->itinerary ?? $this->translations->first()?->itinerary ?? '',
            'what_includes' => $translation?->what_includes ?? $this->translations->first()?->what_includes ?? '',
            'what_not_includes' => $translation?->what_not_includes ?? $this->translations->first()?->what_not_includes ?? '',
            'recommendations' => $translation?->recommendations ?? $this->translations->first()?->recommendations ?? '',
            'what_to_bring' => $translation?->what_to_bring ?? $this->translations->first()?->what_to_bring ?? '',
            'policies' => $translation?->policies ?? $this->translations->first()?->policies ?? '',
            'cancellation_policy' => $translation?->cancellation_policy ?? $this->translations->first()?->cancellation_policy ?? '',

            'service_type' => $this->service_type,
            'difficulty' => $this->difficulty,
            'target_audience' => $this->target_audience,
            'status' => $this->status,
            'active' => $this->active,
            'max_capacity' => $this->capacity,
            'cupos' => $this->cupos,

            'duration_days' => $this->duration_days,
            'duration_hours' => $this->duration_hours,
            'duration_quantity' => $this->duration_quantity,
            'duration_unit' => $this->duration_unit,
            'departure_time' => $this->departure_time,
            'departure_times' => $this->departure_times ?? [],
            'departure_period' => $this->departure_period,
            'timezone' => $this->timezone,

            // Pickup and Location Info
            'enable_meeting_point' => $this->enable_meeting_point ?? false,
            'enable_hotel_pickup' => $this->enable_hotel_pickup ?? false,
            'meeting_point_description' => $this->meeting_point_description,
            'meeting_point_lat' => $this->meeting_point_lat,
            'meeting_point_lng' => $this->meeting_point_lng,
            'pickup_location_description' => $this->pickup_location_description,
            'pickup_center_lat' => $this->pickup_center_lat,
            'pickup_center_lng' => $this->pickup_center_lng,
            'pickup_radius_km' => $this->pickup_radius_km,
            'dropoff_location_description' => $this->dropoff_location_description,

            // Booking Options
            'policy_type' => $this->policy_type ?? 'standard',
            'policy_description' => $this->policy_description,
            'policy_description_custom' => $this->policy_description_custom,
            'booking_anticipation_quantity' => $this->booking_anticipation_quantity ?? 24,
            'booking_anticipation_unit' => $this->booking_anticipation_unit ?? 'hours',
            'data_requirement' => $this->data_requirement ?? 1,
            'operational_info_required' => $this->operational_info_required ?? [],
            'personal_info_required' => $this->personal_info_required ?? ['first_name', 'last_name', 'email', 'phone_whatsapp'],
            'guide_type' => $this->guide_type ?? 'live_guide',
            'guide_languages' => $this->guide_languages ?? [1, 2],

            // Step 8 Availability
            'require_availability' => $this->require_availability ?? false,
            'availability_data' => $this->availability_data,
            // Extract blocks and offers from availability_data (where admin stores them)
            'blocks_data' => $this->availability_data['blocks'] ?? $this->blocks_data ?? [],
            'offers_data' => $this->availability_data['offers'] ?? $this->offers_data ?? [],
            'special_days' => $this->availability_data['specialDays'] ?? [],

            'featured_image' => $this->featured_image_path,
            'thumbnail' => $this->thumbnail_path,
            // Video: per-translation only (no fallback to other languages)
            'youtube_url' => $translation?->youtube_url ?? '',
            'video_first' => $this->video_first ?? true,
            'gallery_layout' => $this->gallery_layout ?? 'hero_mosaic',
            // Media alt/title texts per translation
            'media_texts' => $translation?->media_texts ?? [],
            // Booking texts per translation (policies, meeting point, pickup descriptions)
            'booking_texts' => $translation?->booking_texts ?? [],

            'currency' => $this->currency ?? 'USD',
            'min_price' => $this->min_price,
            'payment_method' => $this->payment_method ?? 'all',
            'booking_anticipation_hours' => $this->booking_anticipation_hours ?? 24,
            'tax_percentage' => $this->tax_percentage,
            'advance_payment_percentage' => $this->advance_payment_percentage,

            'city' => [
                'id' => $this->city_id,
                'name' => $this->city_name ?? $this->city?->name,
            ],

            'primary_language' => [
                'id' => $this->primary_language_id,
                'code' => $this->primaryLanguage?->code ?? 'ES',
            ],

            'price_details' => $this->whenLoaded('prices', function () {
                return $this->prices->map(function($price) {
                    return [
                        'id' => $price->id,
                        'age_stage_id' => $price->age_stage_id,
                        'nationality_id' => $price->nationality_id,
                        'price' => $price->amount,
                        'min_quantity' => $price->min_quantity,
                        'max_quantity' => $price->max_quantity,
                        'active' => $price->active,
                        'age_stage' => $price->ageStage ? [
                            'id' => $price->ageStage->id,
                            'name' => $price->ageStage->description,
                            'description' => $price->ageStage->description,
                            'min_age' => $price->ageStage->min_age,
                            'max_age' => $price->ageStage->max_age,
                        ] : null,
                        'nationality' => $price->nationality ? [
                            'id' => $price->nationality->id,
                            'name' => $price->nationality->name,
                            'code' => $price->nationality->code ?? '',
                        ] : null,
                    ];
                });
            }),

            'media_gallery' => $this->whenLoaded('mediaGallery', function () {
                return $this->mediaGallery->map(function($media) {
                    return [
                        'id' => $media->id,
                        'url' => \Illuminate\Support\Facades\Storage::disk('public')->url($media->image_path),
                        'alt_text' => $media->alt_text ?? '',
                        'title_text' => $media->title_text ?? '',
                        'description' => $media->description ?? '',
                        'is_primary' => (bool)$media->is_primary,
                        'order' => $media->order,
                    ];
                });
            }),

            'map_points' => $this->whenLoaded('mapPoints', function () {
                return $this->mapPoints->map(function($point) {
                    $latLng = $point->lat_lng;
                    return [
                        'id' => $point->id,
                        'name' => $point->name,
                        'description' => $point->description,
                        'coordinates' => $point->coordinates,
                        'lat' => $latLng['lat'],
                        'lng' => $latLng['lng'],
                        'type' => $point->type,
                        'type_label' => $point->type_label,
                        'order' => $point->order,
                    ];
                });
            }),

            'categories' => CategoryResource::collection($this->whenLoaded('categories')),

            // 'faqs' => TourFaqResource::collection($this->whenLoaded('faqs')),

            // 'schema_markup' => $this->whenLoaded('schemaMarkup', function () {
            //     return $this->schemaMarkup->map(function($schema) {
            //         return [
            //             'type' => $schema->schema_type,
            //             'data' => $schema->schema_data,
            //         ];
            //     });
            // }),

            'translations' => $this->translations->map(function($trans) {
                return [
                    'id' => $trans->id,
                    'language_id' => $trans->language_id,
                    'language' => [
                        'id' => $trans->language->id,
                        'code' => $trans->language->code,
                    ],
                    'slug' => $trans->slug,
                    'h1_title' => $trans->h1_title,
                    'meta_title' => $trans->meta_title,
                    'meta_description' => $trans->meta_description,
                    'short_description' => $trans->short_description,
                    'long_description' => $trans->long_description,
                    'itinerary' => $trans->itinerary,
                    'what_includes' => $trans->what_includes,
                    'what_not_includes' => $trans->what_not_includes,
                    'recommendations' => $trans->recommendations,
                    'what_to_bring' => $trans->what_to_bring,
                    'policies' => $trans->policies,
                    'cancellation_policy' => $trans->cancellation_policy,
                    'youtube_url' => $trans->youtube_url,
                    'media_texts' => $trans->media_texts ?? [],
                    'booking_texts' => $trans->booking_texts ?? [],
                ];
            }),

            'is_bookable' => $this->isBookable(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}