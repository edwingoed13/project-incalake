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
                'slug' => $this->city?->slug,
            ],
            'service_type' => $this->service_type,
            'tags' => $this->whenLoaded('tags', function () use ($request) {
                $lang = strtoupper($request->language ?? 'ES');
                return $this->tags->map(fn ($t) => [
                    'id' => $t->id,
                    'slug' => $t->slug,
                    'name' => $t->nameFor($lang),
                ]);
            }, []),
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
            // Detailed translation info for admin listing
            'translations_summary' => $this->whenLoaded('translations', function () {
                return $this->translations->map(function ($translation) {
                    return [
                        'translation_id' => $translation->id,
                        'language_id' => $translation->language_id,
                        'language_code' => $translation->language->code ?? null,
                        'language_country' => $translation->language->country ?? null,
                        'title' => $translation->h1_title,
                        'slug' => $translation->slug,
                        'short_description' => $translation->short_description,
                    ];
                })->filter(function ($t) {
                    return $t['language_code'] !== null;
                })->values();
            }),
            // Languages whose title also belongs to a DIFFERENT tour.
            //
            // A tour copied and then only partly rewritten is invisible in the
            // listing: it shows its own code, its own price and a healthy green
            // "Publicado", while five of its languages still sell the tour it
            // was copied from. That is not hypothetical — the Uyuni tour was
            // serving the Uros tour in EN, FR, DE, PT and IT at the Uyuni price.
            //
            // The comparison is on the title itself, not on a slug suffix: a
            // suffix only records that a collision happened once, possibly with
            // a tour since deleted or renamed, which flagged half the catalogue
            // when tried. Two tours sharing a title today is the actual problem.
            // Behind a flag: this costs a query per tour, and the same
            // resource serves the public listing, which the sitemap source
            // fetches 1000 rows from at a time. Only the admin listing asks.
            'duplicate_title_languages' => $this->when(
                $request->boolean('with_duplicates') && $this->relationLoaded('translations'),
                function () {
                $titles = $this->translations
                    ->filter(fn ($t) => filled($t->h1_title) && $t->language_id)
                    ->map(fn ($t) => [
                        'language_id' => $t->language_id,
                        'code' => $t->language->code ?? null,
                        'title' => $t->h1_title,
                    ])
                    ->filter(fn ($t) => $t['code'] !== null);

                if ($titles->isEmpty()) {
                    return [];
                }

                // Carry the other tour's CODE, not just the language. "EN is
                // duplicated" leaves the operator hunting; "EN is the same
                // title as ES007" is the whole answer, and ES007 is what they
                // read on the row above.
                $clashes = \DB::table('tour_translations as tt')
                    ->join('tours as t', 't.id', '=', 'tt.tour_id')
                    ->where('tt.tour_id', '!=', $this->id)
                    ->whereIn('tt.language_id', $titles->pluck('language_id')->unique()->all())
                    ->whereIn('tt.h1_title', $titles->pluck('title')->unique()->all())
                    ->get(['tt.language_id', 'tt.h1_title', 't.code']);

                if ($clashes->isEmpty()) {
                    return [];
                }

                $byKey = [];
                foreach ($clashes as $c) {
                    $byKey[$c->language_id . '|' . $c->h1_title][] = $c->code;
                }

                $out = [];
                foreach ($titles as $t) {
                    $key = $t['language_id'] . '|' . $t['title'];
                    if (isset($byKey[$key])) {
                        $out[$t['code']] = array_values(array_unique($byKey[$key]));
                    }
                }

                return $out;
                }
            ),
            'difficulty' => $this->difficulty,
            'status' => $this->status,
            'active' => $this->active,
            // Unpublished wizard edits. Present only when the listing asked
            // for it (withExists), so other callers pay nothing.
            'has_pending_draft' => $this->when(
                isset($this->revision_exists),
                fn () => (bool) $this->revision_exists
            ),
            // withMax() hands back the raw DB datetime ("2026-08-27 14:32:11"),
            // not a cast Carbon like created_at/updated_at. Parsing it here is
            // what makes it serialize as ISO-8601: the raw form is read as
            // local time by some browsers and rejected outright by others, and
            // the badge would silently degrade to a dash.
            'pending_draft_at' => $this->when(
                isset($this->revision_updated_at),
                fn () => \Illuminate\Support\Carbon::parse($this->revision_updated_at)
            ),
            'duration_days' => $this->duration_days,
            'duration_hours' => $this->duration_hours,
            'duration_quantity' => $this->duration_quantity,
            'duration_unit' => $this->duration_unit,
            'capacity' => $this->capacity,
            'cupos' => $this->cupos,
            'departure_time' => $this->departure_time,
            'departure_times' => $this->departure_times ?? [],
            'departure_period' => $this->departure_period,
            'timezone' => $this->timezone,
            'tax_percentage' => $this->tax_percentage,
            'advance_payment_percentage' => $this->advance_payment_percentage,
            'featured_image' => $featuredImageUrl,
            'thumbnail' => $thumbnailUrl,
            'min_price' => $this->min_price,
            'is_bookable' => $this->isBookable(),
            'availability_data' => $this->availability_data,
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