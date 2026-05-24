<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Lightweight tour payload for the public listing grid (?light=1).
 *
 * Reads ONLY from eager-loaded relations — never calls getTranslation() /
 * getMinPriceAttribute(), which run fresh queries per tour (N+1). This keeps
 * the listing response small and the query count flat.
 */
class TourCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lang = strtoupper($request->language ?? 'ES');

        // Pick the requested-language translation from the loaded collection
        // (fallback to the first), without hitting the DB.
        $tr = null;
        if ($this->relationLoaded('translations')) {
            $tr = $this->translations->first(fn ($t) => optional($t->language)->code === $lang)
                ?? $this->translations->first();
        }

        // Featured image: direct column, else first gallery item (loaded).
        $img = $this->featured_image_path;
        if (!$img && $this->relationLoaded('mediaGallery') && $this->mediaGallery->count()) {
            $img = $this->mediaGallery->sortBy('order')->first()?->image_path;
        }
        $imgUrl = $img
            ? (str_starts_with($img, 'http') ? $img : Storage::disk('public')->url($img))
            : null;

        // Min price from loaded prices (primary age stage = lowest age_stage_id).
        $minPrice = null;
        if ($this->relationLoaded('prices')) {
            $active = $this->prices->where('active', true);
            if ($active->count()) {
                $firstStage = $active->sortBy('age_stage_id')->first()?->age_stage_id;
                $stage = $firstStage ? $active->where('age_stage_id', $firstStage) : $active;
                $minPrice = $stage->min('amount');
            }
        }

        return [
            'id' => $this->id,
            'title' => $tr?->h1_title ?? $this->code,
            'slug' => $tr?->slug,
            'short_description' => $tr?->short_description,
            'city' => [
                'name' => $this->city_name,
                'slug' => $this->relationLoaded('city') ? $this->city?->slug : null,
            ],
            'duration_days' => $this->duration_days,
            'duration_hours' => $this->duration_hours,
            'duration_quantity' => $this->duration_quantity,
            'duration_unit' => $this->duration_unit,
            'featured_image' => $imgUrl,
            'thumbnail' => $imgUrl,
            'min_price' => $minPrice !== null ? (float) $minPrice : 0,
            'availability_data' => $this->availability_data,
        ];
    }
}
