<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'base_price' => (float) $this->base_price,
            'currency' => $this->currency,
            'sku' => $this->sku,
            'status' => $this->status,
            'design' => new DesignResource($this->whenLoaded('design')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            // Same aggregate the reviews endpoint's `meta` reports, carried on the
            // product itself so the product page can build JSON-LD aggregateRating
            // without a second request. Null average/0 count when there are no
            // reviews yet, so the frontend can omit aggregateRating entirely rather
            // than fabricate one — Google's structured-data guidelines require real
            // review data behind any rating shown.
            'average_rating' => $this->reviews_avg_rating !== null ? round((float) $this->reviews_avg_rating, 1) : null,
            'reviews_count' => (int) ($this->reviews_count ?? 0),
        ];
    }
}
