<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            
            // Relationships
            'language' => new LanguageResource($this->whenLoaded('language')),
            'category_code' => $this->whenLoaded('categoryCode'),
            'user' => new UserResource($this->whenLoaded('user')),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
