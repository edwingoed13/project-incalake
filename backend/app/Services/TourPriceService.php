<?php

namespace App\Services;

use App\Models\AgeStage;
use App\Models\Tour;
use App\Models\TourPrice;
use Illuminate\Support\Facades\Log;
use Exception;

class TourPriceService
{
    public function syncPrices(Tour $tour, array $pricesData): void
    {
        try {
            foreach ($pricesData as $key => $priceData) {
                $ageStageId = $this->resolveAgeStageId($key, $priceData);
                if (!$ageStageId) {
                    continue;
                }

                $tour->prices()->where('age_stage_id', $ageStageId)->delete();

                if (isset($priceData['active']) && $priceData['active']) {
                    foreach (($priceData['ranges'] ?? []) as $range) {
                        if (isset($range['price']) && $range['price'] > 0) {
                            $tour->prices()->create([
                                'age_stage_id' => $ageStageId,
                                'price_type' => 'per_quantity',
                                'amount' => $range['price'],
                                'min_quantity' => $range['from'] ?? 1,
                                'max_quantity' => $range['to'] ?? null,
                                'active' => true,
                            ]);
                        }
                    }
                }
            }

            Log::info("Prices synced successfully", ['tour_id' => $tour->id]);
        } catch (Exception $e) {
            Log::error("Error syncing prices", ['tour_id' => $tour->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Resolve the backend age_stages.id from the admin payload. Accepts either
     * a numeric age_stage id or a description (e.g. "Adulto"). When the row
     * does not exist but the payload carries description + min_age + max_age,
     * create it so new stages from the wizard land consistently.
     */
    private function resolveAgeStageId($key, array $priceData): ?int
    {
        if (is_numeric($key) && AgeStage::whereKey((int) $key)->exists()) {
            return (int) $key;
        }

        $description = $priceData['description'] ?? (is_string($key) ? $key : null);
        if (!$description) {
            return null;
        }

        $stage = AgeStage::whereRaw('LOWER(description) = ?', [mb_strtolower(trim($description))])->first();
        if ($stage) {
            return $stage->id;
        }

        $stage = AgeStage::create([
            'description' => $description,
            'min_age' => (int) ($priceData['min_age'] ?? 0),
            'max_age' => (int) ($priceData['max_age'] ?? 99),
            'editable' => true,
        ]);
        return $stage->id;
    }

    public function getTourPrice(Tour $tour, int $ageStageId, int $quantity): ?float
    {
        $price = $tour->prices()
            ->where('age_stage_id', $ageStageId)
            ->where('active', true)
            ->where('min_quantity', '<=', $quantity)
            ->where(function($query) use ($quantity) {
                $query->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->first();

        return $price ? $price->amount : null;
    }

    public function getMinPrice(Tour $tour): ?float
    {
        return $tour->prices()->where('active', true)->min('amount');
    }

    public function getMaxPrice(Tour $tour): ?float
    {
        return $tour->prices()->where('active', true)->max('amount');
    }
}