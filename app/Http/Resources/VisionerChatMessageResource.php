<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisionerChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'epic_id' => $this->epic_id,
            'epic_title' => $this->whenLoaded('epic', fn () => $this->epic?->title),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
