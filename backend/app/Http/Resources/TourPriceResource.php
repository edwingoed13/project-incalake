<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'age_stage' => [
                'id' => $this->age_stage_id,
                'name' => $this->ageStage->description ?? '',
                'min_age' => $this->ageStage->min_age ?? 0,
                'max_age' => $this->ageStage->max_age ?? 999,
            ],
            'price_type' => $this->price_type,
            'amount' => (float) $this->amount,
            'min_quantity' => $this->min_quantity,
            'max_quantity' => $this->max_quantity,
            'active' => $this->active,
        ];
    }
}