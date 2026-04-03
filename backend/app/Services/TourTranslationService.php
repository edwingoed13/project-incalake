<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourTranslation;
use Illuminate\Support\Facades\Log;
use Exception;

class TourTranslationService
{
    public function syncTranslations(Tour $tour, array $translationsData): void
    {
        try {
            foreach ($translationsData as $languageId => $translationData) {
                if (!empty($translationData['h1_title'])) {
                    // Ensure slug is unique (exclude current tour's translation)
                    if (!empty($translationData['slug'])) {
                        $slug = $translationData['slug'];
                        $existing = TourTranslation::where('slug', $slug)
                            ->where('tour_id', '!=', $tour->id)
                            ->first();
                        if ($existing) {
                            $counter = 1;
                            while (TourTranslation::where('slug', $slug . '-' . $counter)
                                ->where('tour_id', '!=', $tour->id)->exists()) {
                                $counter++;
                            }
                            $translationData['slug'] = $slug . '-' . $counter;
                        }
                    }

                    $tour->translations()->updateOrCreate(
                        ['language_id' => $languageId],
                        $translationData
                    );
                }
            }

            foreach ($translationsData as $lid => $td) {
                Log::info("Translation saved", ['tour_id' => $tour->id, 'lang_id' => $lid, 'has_booking_texts' => isset($td['booking_texts']), 'booking_texts_type' => gettype($td['booking_texts'] ?? null), 'booking_texts_keys' => is_array($td['booking_texts'] ?? null) ? array_keys($td['booking_texts']) : 'N/A']);
                break; // only log first
            }
        } catch (Exception $e) {
            Log::error("Error syncing translations", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getTranslationForLanguage(Tour $tour, int $languageId): ?TourTranslation
    {
        return $tour->translations()->where('language_id', $languageId)->first();
    }

    public function autoFillSeoFields(array &$translationData): void
    {
        if (empty($translationData['meta_title']) && !empty($translationData['h1_title'])) {
            $translationData['meta_title'] = mb_strimwidth($translationData['h1_title'], 0, 60, '');
        }
        if (empty($translationData['meta_description']) && !empty($translationData['short_description'])) {
            $translationData['meta_description'] = mb_strimwidth($translationData['short_description'], 0, 160, '');
        }
        if (empty($translationData['og_title'])) {
            $translationData['og_title'] = $translationData['meta_title'] ?? '';
        }
        if (empty($translationData['og_description'])) {
            $translationData['og_description'] = $translationData['meta_description'] ?? '';
        }
        if (empty($translationData['twitter_title'])) {
            $translationData['twitter_title'] = $translationData['meta_title'] ?? '';
        }
        if (empty($translationData['twitter_description'])) {
            $translationData['twitter_description'] = $translationData['meta_description'] ?? '';
        }
    }
}