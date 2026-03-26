<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TourMediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'language_id' => $this->language_id,
            'url' => \Storage::url($this->image_path),
            'path' => $this->image_path,
            'alt_text' => $this->alt_text,
            'title_text' => $this->title_text,
            'order' => $this->order,
        ];
    }
}