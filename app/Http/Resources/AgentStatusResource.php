<?php

namespace App\Http\Resources;

use App\Models\ProjectTask;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AgentStatusResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // current_task used to be a free-text field an admin typed in and that went
        // stale the moment nobody remembered to update it (the exact bug fixed earlier
        // this session). It's now derived live from project_tasks — the same source of
        // truth the progress board reads from — so the two dashboards can't drift apart.
        $liveTask = ProjectTask::where('agent_name', $this->agent_name)->where('status', 'in_progress')->latest('updated_at')->first()
            ?? ProjectTask::where('agent_name', $this->agent_name)->where('status', 'done')->latest('updated_at')->first();

        return [
            'id' => $this->id,
            'agent_name' => $this->agent_name,
            'status' => $this->status,
            'current_task' => $liveTask?->title ?? $this->current_task,
            'current_task_status' => $liveTask?->status,
            'backlog_count' => ProjectTask::where('agent_name', $this->agent_name)->where('status', 'todo')->count(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
