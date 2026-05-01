<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $language = strtoupper($request->language ?? 'ES');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->nameFor($language),
            'translations' => $this->translations ?? [],
            'active' => (bool) $this->active,
            'tours_count' => $this->when(isset($this->tours_count), $this->tours_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
