<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class TourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get featured image: direct field or first gallery image
        $featuredImage = $this->featured_image_path;
        if (!$featuredImage && $this->relationLoaded('mediaGallery') && $this->mediaGallery->count() > 0) {
            $firstMedia = $this->mediaGallery->first();
            $featuredImage = $firstMedia->image_path;
        }

        // Build full URL for image
        $featuredImageUrl = null;
        if ($featuredImage) {
            if (str_starts_with($featuredImage, 'http')) {
                $featuredImageUrl = $featuredImage;
            } else {
                $featuredImageUrl = Storage::disk('public')->url($featuredImage);
            }
        }

        // Thumbnail: direct field or same as featured
        $thumbnail = $this->thumbnail_path;
        $thumbnailUrl = null;
        if ($thumbnail) {
            $thumbnailUrl = str_starts_with($thumbnail, 'http') ? $thumbnail : Storage::disk('public')->url($thumbnail);
        } else {
            $thumbnailUrl = $featuredImageUrl;
        }

        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->getName($request->language ?? 'ES'),
            'slug' => $this->getSlug($request->language ?? 'ES'),
            'short_description' => $this->getTranslation($request->language ?? 'ES')?->short_description,
            'city' => [
                'id' => $this->city_id,
                'name' => $this->city_name,
            ],
            'service_type' => $this->service_type,
            // Available languages for this tour
            'available_languages' => $this->whenLoaded('translations', function () {
                return $this->translations->map(function ($translation) {
                    return [
                        'id' => $translation->language_id,
                        'code' => $translation->language->code ?? null,
                        'country' => $translation->language->country ?? null,
                    ];
                })->filter(function ($lang) {
                    return $lang['code'] !== null;
                })->values();
            }),
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'active' => $this->active,
            'duration_days' => $this->duration_days,
            'duration_hours' => $this->duration_hours,
            'capacity' => $this->capacity,
            'cupos' => $this->cupos,
            'departure_time' => $this->departure_time,
            'departure_period' => $this->departure_period,
            'timezone' => $this->timezone,
            'tax_percentage' => $this->tax_percentage,
            'advance_payment_percentage' => $this->advance_payment_percentage,
            'featured_image' => $featuredImageUrl,
            'thumbnail' => $thumbnailUrl,
            'min_price' => $this->min_price,
            'is_bookable' => $this->isBookable(),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'media_gallery' => $this->whenLoaded('mediaGallery', function () {
                return $this->mediaGallery->map(function ($media) {
                    $url = $media->image_path;
                    if ($url && !str_starts_with($url, 'http')) {
                        $url = Storage::disk('public')->url($url);
                    }
                    return [
                        'id' => $media->id,
                        'url' => $url,
                        'alt_text' => $media->alt_text,
                        'title_text' => $media->title_text,
                        'order' => $media->order,
                    ];
                });
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}