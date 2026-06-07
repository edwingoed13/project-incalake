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

        // Offer badge. We surface BOTH currently-active offers and upcoming
        // ones (anything whose endDate >= today in Lima time), so a tour with
        // a promo coming up still flags it on the listing. Past offers are
        // skipped. Among valid offers we prefer the one active right now;
        // otherwise we take the soonest upcoming. The listing cache key
        // carries today's date, so badges flip on the correct day.
        $offer = null;
        $av = $this->availability_data;
        if (is_string($av)) {
            $av = json_decode($av, true) ?: [];
        }
        $offers = is_array($av) ? ($av['offers'] ?? []) : [];
        $today = now('America/Lima')->toDateString();

        $valid = array_values(array_filter(
            $offers,
            fn ($o) => ($o['endDate'] ?? '') >= $today
        ));
        usort($valid, fn ($a, $b) => ($a['startDate'] ?? '') <=> ($b['startDate'] ?? ''));

        $picked = null;
        foreach ($valid as $o) {
            if (($o['startDate'] ?? '') <= $today) { $picked = $o; break; }
        }
        if (!$picked && !empty($valid)) {
            $picked = $valid[0];
        }

        if ($picked) {
            $rawDiscount = (float) ($picked['discount'] ?? 0);
            $isPercentage = ($picked['discountType'] ?? '') === 'percentage';
            $isActive = ($picked['startDate'] ?? '') <= $today;

            // Round the discount to integer for the label (most offers are
            // whole numbers; avoids "15.00% OFF").
            $label = $isPercentage
                ? (rtrim(rtrim(number_format($rawDiscount, 2, '.', ''), '0'), '.') . '% OFF')
                : ('$' . rtrim(rtrim(number_format($rawDiscount, 2, '.', ''), '0'), '.') . ' OFF');

            // Discounted min price (for the listing strikethrough). Computed
            // server-side so every surface that consumes the resource agrees.
            $discountedMinPrice = null;
            if ($minPrice !== null && $minPrice > 0) {
                $discountedMinPrice = $isPercentage
                    ? round((float) $minPrice * (1 - $rawDiscount / 100), 2)
                    : round(max(0.0, (float) $minPrice - $rawDiscount), 2);
            }

            $offer = [
                'label' => $label,
                'discount' => $rawDiscount,
                'discount_type' => $isPercentage ? 'percentage' : 'fixed',
                'start_date' => $picked['startDate'] ?? null,
                'end_date' => $picked['endDate'] ?? null,
                'is_active' => $isActive,
                'is_upcoming' => !$isActive,
                'discounted_min_price' => $discountedMinPrice,
            ];
        }

        // Sightseeing places only — the listing's "Lugares" filter and the
        // search scoring use these. Restaurants / airports / meeting points
        // are filtered out because they're never something a user searches
        // for to find a tour.
        $places = [];
        if ($this->relationLoaded('mapPoints')) {
            $places = $this->mapPoints
                ->whereIn('type', ['lugar_turistico', 'museo', 'punto_parada'])
                ->map(fn ($p) => ['name' => $p->name, 'type' => $p->type])
                ->values()
                ->all();
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
            'offer' => $offer,
            'places' => $places,
        ];
    }
}
