<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateTourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'primary_language_id' => 'sometimes|required|exists:languages,id',
            'city_id' => 'nullable|exists:cities,id',
            'city_name' => 'sometimes|required|string|max:255',
            'city_latitude' => 'nullable|numeric|between:-90,90',
            'city_longitude' => 'nullable|numeric|between:-180,180',
            'code' => 'nullable|string|max:100|unique:tours,code,' . $this->route('id'),
            'service_type' => 'sometimes|required|in:tour,package,experience,transport',
            'status' => 'nullable|in:draft,published,archived',
            'difficulty' => 'sometimes|required|in:easy,moderate,hard',
            'target_audience' => 'sometimes|required|in:all,families,adults,adventure,seniors',
            'capacity' => 'sometimes|required|integer|min:1|max:999',
            'cupos' => 'nullable|integer|min:0',

            'departure_time' => 'nullable|string|max:5',
            'departure_period' => 'nullable|in:AM,PM',
            'duration_quantity' => 'nullable|numeric|min:0',
            'duration_unit' => 'nullable|in:days,hours,minutes',
            'timezone' => 'nullable|string|max:50',

            'start_time' => 'nullable|string',
            'booking_anticipation_hours' => 'nullable|integer|min:0',
            'payment_method' => 'nullable|in:paypal,culqi,all',
            'data_requirement' => 'nullable|integer|in:1,2,3',
            
            // Step 6 Booking Options
            'policy_type' => 'nullable|string',
            'policy_description' => 'nullable|string',
            'policy_description_custom' => 'nullable|string',
            'booking_anticipation_quantity' => 'nullable|integer',
            'booking_anticipation_unit' => 'nullable|in:hours,days,weeks,months',
            'operational_info_required' => 'nullable|array',
            'personal_info_required' => 'nullable|array',
            'enable_meeting_point' => 'nullable|boolean',
            'enable_hotel_pickup' => 'nullable|boolean',
            'meeting_point_description' => 'nullable|string',
            'meeting_point_lat' => 'nullable|numeric',
            'meeting_point_lng' => 'nullable|numeric',
            'pickup_location_description' => 'nullable|string',
            'pickup_center_lat' => 'nullable|numeric',
            'pickup_center_lng' => 'nullable|numeric',
            'pickup_radius_km' => 'nullable|numeric',
            'dropoff_location_description' => 'nullable|string',
            'guide_type' => 'nullable|string',
            'guide_languages' => 'nullable|array',
            
            // Step 8 Availability
            'require_availability' => 'nullable|boolean',
            'availability_data' => 'nullable|array',
            'blocks_data' => 'nullable|array',
            'offers_data' => 'nullable|array',

            'index_status' => 'nullable|in:index,noindex',
            'follow_status' => 'nullable|in:follow,nofollow',
            'active' => 'nullable|boolean',
            'youtube_url' => 'nullable|string|max:255',
            'gallery_layout' => 'nullable|in:hero_mosaic,full_width_hero,video_image,masonry_grid',

            'translations' => 'sometimes|required|array',
            'translations.*.language_id' => 'required_with:translations|exists:languages,id',
            'translations.*.h1_title' => 'required_with:translations|nullable|string|max:100',
            'translations.*.meta_title' => 'nullable|string|max:60',
            'translations.*.meta_description' => 'nullable|string|max:160',
            'translations.*.slug' => 'nullable|string|max:150',
            'translations.*.short_description' => 'nullable|string',
            'translations.*.long_description' => 'nullable|string',
            'translations.*.itinerary' => 'nullable|string',
            'translations.*.what_includes' => 'nullable|string',
            'translations.*.what_not_includes' => 'nullable|string',
            'translations.*.recommendations' => 'nullable|string',
            'translations.*.what_to_bring' => 'nullable|string',
            'translations.*.policies' => 'nullable|string',
            'translations.*.cancellation_policy' => 'nullable|string',
            'translations.*.og_title' => 'nullable|string|max:60',
            'translations.*.og_description' => 'nullable|string|max:160',
            'translations.*.twitter_title' => 'nullable|string|max:60',
            'translations.*.twitter_description' => 'nullable|string|max:160',
            'translations.*.ads_headline' => 'nullable|string|max:100',
            'translations.*.ads_description' => 'nullable|string|max:160',
            'translations.*.cta_text' => 'nullable|string|max:50',
            'translations.*.youtube_url' => 'nullable|string|max:500',
            'translations.*.media_texts' => 'nullable|array',
            'translations.*.booking_texts' => 'nullable|array',

            'prices' => 'sometimes|required|array',
            'prices.*' => 'array',
            'prices.*.active' => 'nullable|boolean',
            'prices.*.ranges' => 'nullable|array',
            'prices.*.ranges.*.from' => 'required_with:prices.*.ranges|integer|min:1',
            'prices.*.ranges.*.to' => 'nullable|integer|min:1',
            'prices.*.ranges.*.price' => 'required_with:prices.*.ranges|numeric|min:0.01',

            'categories' => 'nullable|array',
            'categories.*' => 'integer|exists:categories_new,id',

            'map_points' => 'nullable|array',
            'map_points.*.name' => 'required|string|max:255',
            'map_points.*.description' => 'nullable|string',
            'map_points.*.coordinates' => 'required|string',
            'map_points.*.type' => 'required|string|max:50',
            'map_points.*.order' => 'required|integer|min:0',

            'temp_images' => 'nullable|array',
            'temp_images.*.filename' => 'required|string',
            'temp_images.*.path' => 'required|string',
            'temp_images.*.alt_text' => 'nullable|string|max:255',
            'temp_images.*.title_text' => 'nullable|string|max:255',
            'temp_images.*.description' => 'nullable|string',
            'temp_images.*.is_primary' => 'nullable|boolean',
            'temp_images.*.order' => 'nullable|integer',

            'media_gallery' => 'nullable|array',
            'media_gallery.*.id' => 'required|integer',
            'media_gallery.*.alt_text' => 'nullable|string|max:255',
            'media_gallery.*.title_text' => 'nullable|string|max:255',
            'media_gallery.*.description' => 'nullable|string',
            'media_gallery.*.is_primary' => 'nullable|boolean',
            'media_gallery.*.order' => 'nullable|integer',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}