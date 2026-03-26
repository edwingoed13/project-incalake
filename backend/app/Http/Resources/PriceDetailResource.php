<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_age' => $this->min_age,
            'max_age' => $this->max_age,
            
            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
            'age_stage' => $this->whenLoaded('ageStage'),
            'nationality' => $this->whenLoaded('nationality'),
            'prices' => $this->whenLoaded('prices'),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
