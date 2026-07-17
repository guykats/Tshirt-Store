<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectTaskResource;
use App\Models\ProjectTask;
use Illuminate\Http\Request;

class ProjectTaskController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tasks = ProjectTask::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('agent'), fn ($q, $agent) => $q->where('agent_name', $agent))
            ->orderByRaw("CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 WHEN 'done' THEN 3 ELSE 4 END")
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        return response()->json([
            'data' => ProjectTaskResource::collection($tasks),
            'counts' => [
                'todo' => ProjectTask::where('status', 'todo')->count(),
                'in_progress' => ProjectTask::where('status', 'in_progress')->count(),
                'blocked' => ProjectTask::where('status', 'blocked')->count(),
                'done' => ProjectTask::where('status', 'done')->count(),
            ],
        ]);
    }
}
