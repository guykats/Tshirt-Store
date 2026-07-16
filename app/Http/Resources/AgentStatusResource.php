<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'agent_name' => $this->agent_name,
            'status' => $this->status,
            'current_task' => $this->current_task,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
