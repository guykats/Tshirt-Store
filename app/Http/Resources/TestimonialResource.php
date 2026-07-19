<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->author_name,
            'author_context_en' => $this->author_context_en,
            'author_context_he' => $this->author_context_he,
            'quote_en' => $this->quote_en,
            'quote_he' => $this->quote_he,
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
