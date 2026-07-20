<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => (float) $this->value,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'max_redemptions' => $this->max_redemptions,
            'redemptions_count' => $this->redemptions_count,
            'active' => (bool) $this->active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
