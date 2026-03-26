<?php

namespace App\Jobs;

use App\Models\Tour;
use App\Models\TourSchemaMarkup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateTourSchemaMarkup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $tourId;
    protected string $language;

    public $tries = 2;

    public function __construct(int $tourId, string $language = 'ES')
    {
        $this->tourId = $tourId;
        $this->language = $language;
        $this->onQueue('seo');
    }

    public function handle(): void
    {
        try {
            $tour = Tour::with(['city', 'translations', 'prices'])->find($this->tourId);

            if (!$tour) {
                Log::warning("Tour not found for schema markup generation", ['tour_id' => $this->tourId]);
                return;
            }

            $translation = $tour->translations->where('language.code', $this->language)->first();

            if (!$translation) {
                Log::warning("Translation not found", [
                    'tour_id' => $this->tourId,
                    'language' => $this->language
                ]);
                return;
            }

            $schema = $this->generateProductSchema($tour, $translation);

            TourSchemaMarkup::updateOrCreate(
                [
                    'tour_id' => $this->tourId,
                    'language_id' => $translation->language_id,
                    'schema_type' => 'Product'
                ],
                [
                    'schema_data' => json_encode($schema),
                ]
            );

            Log::info("Schema markup updated successfully", [
                'tour_id' => $tour->id,
                'language' => $this->language
            ]);
        } catch (Exception $e) {
            Log::error("Error updating tour schema markup", [
                'tour_id' => $this->tourId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function generateProductSchema(Tour $tour, $translation): array
    {
        $minPrice = $tour->prices->min('amount') ?? 0;

        return [
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => $translation->h1_title,
            'description' => $translation->meta_description ?? $translation->short_description,
            'image' => $tour->featured_image_path,
            'brand' => [
                '@type' => 'Brand',
                'name' => 'Incalake'
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => url("/tours/{$tour->id}"),
                'priceCurrency' => 'USD',
                'price' => $minPrice,
                'availability' => $tour->is_bookable() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
            'duration' => $tour->getFullDurationAttribute(),
            'location' => [
                '@type' => 'Place',
                'name' => $tour->city_name,
            ],
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.5',
                'reviewCount' => '10',
            ],
        ];
    }

    public function failed(Exception $exception): void
    {
        Log::error("Failed to update tour schema markup", [
            'tour_id' => $this->tourId,
            'error' => $exception->getMessage()
        ]);
    }
}