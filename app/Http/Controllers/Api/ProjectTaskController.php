<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectTaskResource;
use App\Models\ProjectTask;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tasks = ProjectTask::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('agent'), fn ($q, $agent) => $q->where('agent_name', $agent))
            ->when($request->query('epic_id'), fn ($q, $epicId) => $q->where('epic_id', $epicId))
            ->orderByRaw("CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        $tally = ProjectTask::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => ProjectTaskResource::collection($tasks),
            'counts' => [
                'todo' => (int) $tally->get('todo', 0),
                'in_progress' => (int) $tally->get('in_progress', 0),
                'blocked' => (int) $tally->get('blocked', 0),
                'done' => (int) $tally->get('done', 0),
            ],
        ]);
    }

    public function approve(Request $request, ProjectTask $projectTask)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $projectTask->update(['approved_for_dev' => true]);

        SystemEvent::log(
            'project_task.approved',
            "Task \"{$projectTask->title}\" approved for development by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['project_task_id' => $projectTask->id],
        );

        return new ProjectTaskResource($projectTask->fresh());
    }

    public function unapprove(Request $request, ProjectTask $projectTask)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $projectTask->update(['approved_for_dev' => false]);

        SystemEvent::log(
            'project_task.unapproved',
            "Approval for task \"{$projectTask->title}\" was revoked by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['project_task_id' => $projectTask->id],
        );

        return new ProjectTaskResource($projectTask->fresh());
    }
}
