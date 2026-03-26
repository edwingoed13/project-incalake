<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'code' => $this->code,
            'nearest_city' => $this->nearest_city,
            'nearest_airport' => $this->nearest_airport,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'capacity' => $this->capacity,
            'attachments' => $this->attachments,
            'status' => (bool) $this->status,
            'policies' => $this->policies,
            'booking_anticipation' => $this->booking_anticipation,
            'data_requirement' => $this->data_requirement,
            'multiple_forms' => (bool) $this->multiple_forms,
            
            // Relationships
            'service' => new ServiceResource($this->whenLoaded('service')),
            'product_code' => $this->whenLoaded('productCode'),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'galleries' => $this->whenLoaded('galleries'),
            'tab' => $this->whenLoaded('tab'),
            'additional_tabs' => $this->whenLoaded('additionalTabs'),
            'price_details' => PriceDetailResource::collection($this->whenLoaded('priceDetails')),
            'availabilities' => $this->whenLoaded('availabilities'),
            'blockouts' => $this->whenLoaded('blockouts'),
            'offers' => $this->whenLoaded('offers'),
            'coupons' => $this->whenLoaded('coupons'),
            'resources' => $this->whenLoaded('resources'),
            'form_fields' => $this->whenLoaded('formFields'),
            'service_details' => $this->whenLoaded('serviceDetails'),
            
            // Timestamps
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }
}
