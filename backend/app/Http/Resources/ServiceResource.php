<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
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
            'url' => $this->url,
            'page_title' => $this->page_title,
            'page_description' => $this->page_description,
            'main_image' => $this->main_image,
            'show_slider' => (bool) $this->show_slider,
            'thumbnail' => $this->thumbnail,
            'rating' => $this->rating,
            'reviews' => $this->reviews,
            'location' => $this->location,
            'uri' => $this->uri,
            
            // Relationships
            'language' => new LanguageResource($this->whenLoaded('language')),
            'service_code' => $this->whenLoaded('serviceCode'),
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
