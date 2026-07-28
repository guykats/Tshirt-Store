<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_date' => $this->batch_date?->toDateString(),
            'motif' => $this->motif,
            'style' => $this->style,
            'status' => $this->status,
            'promoted_design_id' => $this->promoted_design_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
