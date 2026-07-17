<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'size' => $this->size,
            'color' => $this->color,
            'sku' => $this->sku,
            'stock_quantity' => $this->stock_quantity,
            'price_override' => $this->price_override !== null ? (float) $this->price_override : null,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
