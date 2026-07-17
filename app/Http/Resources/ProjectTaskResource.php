<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'agent_name' => $this->agent_name,
            'status' => $this->status,
            'task_type' => $this->task_type,
            'commit_sha' => $this->commit_sha,
            'screenshot_url' => $this->screenshot_path ? Storage::url($this->screenshot_path) : null,
            'blocked_reason' => $this->blocked_reason,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
