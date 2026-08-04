<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EpicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'agent_name' => $this->agent_name,
            'status' => $this->status,
            'priority' => $this->priority,
            'task_count' => $this->whenCounted('tasks'),
            // Only set (via EpicController::index) for approved epics - a
            // rollup of "how are this epic's linked project_tasks doing"
            // only makes sense once there's something to roll up. Left
            // unset (and thus null here) for proposed/rejected epics and
            // for the single-epic approve/reject/delay responses, which
            // don't compute it.
            'build_status' => $this->build_status,
            'decided_by' => $this->whenLoaded('decidedBy', fn () => $this->decidedBy?->name),
            'decided_at' => $this->decided_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
