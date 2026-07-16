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
        ];
    }
}
