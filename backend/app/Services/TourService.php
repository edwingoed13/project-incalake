<?php

namespace App\Services;

use App\Models\Tour;
use App\Enums\TourStatus;
use App\Enums\ServiceType;
use App\Enums\Difficulty;
use App\Enums\TargetAudience;
use App\Enums\PaymentMethod;
use App\Enums\DurationUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class TourService
{
    protected TourPriceService $priceService;
    protected TourTranslationService $translationService;
    protected TourMediaService $mediaService;

    public function __construct(
        TourPriceService $priceService,
        TourTranslationService $translationService,
        TourMediaService $mediaService
    ) {
        $this->priceService = $priceService;
        $this->translationService = $translationService;
        $this->mediaService = $mediaService;
    }

    public function create(array $data): Tour
    {
        DB::beginTransaction();
        try {
            $data = $this->prepareDurationData($data);
            $data = $this->convertEnumsToValues($data);

            $tour = Tour::create($data);

            if (isset($data['translations'])) {
                $this->translationService->syncTranslations($tour, $data['translations']);
            }

            if (isset($data['prices'])) {
                $this->priceService->syncPrices($tour, $data['prices']);
            }

            if (isset($data['categories'])) {
                $tour->categories()->sync($data['categories']);
            }

            if (isset($data['map_points'])) {
                $this->syncMapPoints($tour, $data['map_points']);
            }

            if (isset($data['temp_images'])) {
                $this->mediaService->processImages($tour, $data['temp_images']);
            }

            DB::commit();
            Log::info("Tour created successfully", ['tour_id' => $tour->id, 'code' => $tour->code]);

            return $tour->load(['translations', 'prices', 'categories', 'city', 'mediaGallery']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error creating tour", ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function update(Tour $tour, array $data): Tour
    {
        $firstLang = isset($data['translations']) ? array_key_first($data['translations']) : null;
        \Log::info('UPDATE_TOUR_DEBUG', [
            'tour_id' => $tour->id,
            'first_lang' => $firstLang,
            'bt' => $firstLang ? ($data['translations'][$firstLang]['booking_texts'] ?? 'MISSING') : 'NO_TRANS',
        ]);
        DB::beginTransaction();
        try {
            $data = $this->prepareDurationData($data);
            $data = $this->convertEnumsToValues($data);

            $tour->update($data);

            if (isset($data['translations'])) {
                $this->translationService->syncTranslations($tour, $data['translations']);
            }

            if (isset($data['prices'])) {
                $this->priceService->syncPrices($tour, $data['prices']);
            }

            if (isset($data['categories'])) {
                $tour->categories()->sync($data['categories']);
            }

            if (isset($data['map_points'])) {
                $this->syncMapPoints($tour, $data['map_points']);
            }

            if (isset($data['media_gallery'])) {
                $this->mediaService->syncMediaGallery($tour, $data['media_gallery']);
            }

            if (isset($data['temp_images'])) {
                $this->mediaService->processImages($tour, $data['temp_images']);
            }

            DB::commit();
            Log::info("Tour updated successfully", ['tour_id' => $tour->id, 'code' => $tour->code]);

            return $tour->load(['translations', 'prices', 'categories', 'city', 'mediaGallery']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error updating tour", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function delete(Tour $tour): bool
    {
        try {
            $tour->delete();
            Log::info("Tour deleted successfully", ['tour_id' => $tour->id]);
            return true;
        } catch (Exception $e) {
            Log::error("Error deleting tour", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function generateTourCode(int $languageId): string
    {
        $language = DB::table('languages')->find($languageId);
        if (!$language) {
            throw new Exception("Language not found");
        }

        $prefix = strtoupper($language->code);
        $lastTour = Tour::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTour) {
            $lastNumber = (int) substr($lastTour->code, strlen($prefix));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    protected function syncMapPoints(Tour $tour, array $mapPoints): void
    {
        $tour->mapPoints()->delete();

        collect($mapPoints)->each(function($point) use ($tour) {
            $tour->mapPoints()->create([
                'name' => $point['name'],
                'description' => $point['description'] ?? null,
                'coordinates' => $point['coordinates'],
                'type' => $point['type'],
                'order' => $point['order'],
            ]);
        });
    }

    protected function prepareDurationData(array $data): array
    {
        if (isset($data['duration_unit']) && isset($data['duration_quantity'])) {
            $unit = is_string($data['duration_unit']) ? $data['duration_unit'] : $data['duration_unit']->value;
            $data['duration_unit'] = $unit;
            $data['duration_hours'] = DurationUnit::tryFrom($unit)?->toHours($data['duration_quantity']) ?? $data['duration_quantity'];
        }

        // Normalize departure_times and sync top-level fields from first schedule (backward compat)
        if (!empty($data['departure_times']) && is_array($data['departure_times'])) {
            $normalized = [];
            foreach ($data['departure_times'] as $item) {
                if (is_string($item)) {
                    if (empty($item)) continue;
                    $normalized[] = [
                        'time' => substr($item, 0, 5),
                        'duration' => (float)($data['duration_quantity'] ?? 1),
                        'duration_unit' => $data['duration_unit'] ?? 'hours',
                    ];
                } elseif (is_array($item) && !empty($item['time'])) {
                    $normalized[] = [
                        'time' => substr($item['time'], 0, 5),
                        'duration' => (float)($item['duration'] ?? 1),
                        'duration_unit' => $item['duration_unit'] ?? 'hours',
                    ];
                }
            }
            $data['departure_times'] = $normalized;

            if (!empty($normalized)) {
                $first = $normalized[0];
                $data['departure_time'] = $first['time'];
                // Sync top-level duration with first schedule for legacy display
                $data['duration_quantity'] = $first['duration'];
                $data['duration_unit'] = $first['duration_unit'];
                $data['duration_hours'] = DurationUnit::tryFrom($first['duration_unit'])?->toHours($first['duration']) ?? $first['duration'];
            }
        }

        return $data;
    }

    protected function convertEnumsToValues(array $data): array
    {
        if (isset($data['status']) && $data['status'] instanceof TourStatus) {
            $data['status'] = $data['status']->value;
        }
        if (isset($data['difficulty']) && $data['difficulty'] instanceof Difficulty) {
            $data['difficulty'] = $data['difficulty']->value;
        }
        if (isset($data['target_audience']) && $data['target_audience'] instanceof TargetAudience) {
            $data['target_audience'] = $data['target_audience']->value;
        }
        if (isset($data['service_type']) && $data['service_type'] instanceof ServiceType) {
            $data['service_type'] = $data['service_type']->value;
        }
        if (isset($data['payment_method']) && $data['payment_method'] instanceof PaymentMethod) {
            $data['payment_method'] = $data['payment_method']->value;
        }

        return $data;
    }

    public function generateSlug(string $title, ?int $tourId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $counter = 1;

        while (DB::table('tour_translations')->where('slug', $slug)->exists()) {
            $translation = DB::table('tour_translations')->where('slug', $slug)->first();
            if ($translation && $tourId && $translation->tour_id == $tourId) {
                return $slug;
            }
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}