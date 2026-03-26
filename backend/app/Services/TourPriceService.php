<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourPrice;
use Illuminate\Support\Facades\Log;
use Exception;

class TourPriceService
{
    public function syncPrices(Tour $tour, array $pricesData): void
    {
        try {
            foreach ($pricesData as $ageStageId => $priceData) {
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