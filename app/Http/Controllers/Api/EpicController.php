<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EpicResource;
use App\Models\Epic;
use App\Models\ProjectTask;
use App\Models\SystemEvent;
use App\Services\GitHubActionsClient;
use Illuminate\Http\Request;

class EpicController extends Controller
{
    /**
     * Maps the MIN() of this CASE-mapped priority (0 = highest precedence)
     * back to a build_status label. Mirrors the exact precedence
     * ProjectTaskController::index uses for the Board's own row ordering:
     * blocked beats in_progress beats todo beats done.
     */
    private const BUILD_STATUS_PRIORITY_CASE = "CASE status WHEN 'blocked' THEN 0 WHEN 'in_progress' THEN 1 WHEN 'todo' THEN 2 WHEN 'done' THEN 3 ELSE 4 END";

    private const BUILD_STATUS_BY_PRIORITY = [0 => 'blocked', 1 => 'in_progress', 2 => 'todo', 3 => 'done'];

    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $epics = Epic::query()
            ->withCount('tasks')
            ->with('decidedBy')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'proposed' THEN 0 WHEN 'approved' THEN 1 WHEN 'rejected' THEN 2 ELSE 3 END")
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();

        // Single aggregate query (no N+1 per epic) mapping every epic that
        // has at least one linked project_task to the highest-precedence
        // status among its tasks, via MIN() over the same CASE-mapped
        // priority style used for portable (SQLite + MySQL) custom
        // ordering elsewhere in this codebase - no FIELD().
        $priorityByEpicId = ProjectTask::query()
            ->whereIn('epic_id', $epics->pluck('id'))
            ->selectRaw('epic_id, MIN('.self::BUILD_STATUS_PRIORITY_CASE.') as priority')
            ->groupBy('epic_id')
            ->pluck('priority', 'epic_id');

        $epics->each(function (Epic $epic) use ($priorityByEpicId) {
            if ($epic->status !== 'approved') {
                $epic->build_status = null;

                return;
            }

            $priority = $priorityByEpicId->get($epic->id);

            // No linked tasks at all -> nothing has started -> 'todo'.
            $epic->build_status = $priority === null
                ? 'todo'
                : (self::BUILD_STATUS_BY_PRIORITY[(int) $priority] ?? 'todo');
        });

        if ($buildStatus = $request->query('build_status')) {
            $epics = $epics->filter(fn (Epic $epic) => $epic->build_status === $buildStatus)->values();
        }

        return EpicResource::collection($epics);
    }

    public function approve(Request $request, Epic $epic, GitHubActionsClient $github)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $epic->update([
            'status' => 'approved',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        SystemEvent::log(
            'epic.approved',
            "Epic \"{$epic->title}\" approved by {$request->user()->name} — ready for the PM to break into tasks.",
            $request->user()->name,
            'user',
            ['epic_id' => $epic->id],
        );

        // A disabled workflow can never fire on its own schedule to notice
        // this - without this, approving an epic while automation happens
        // to be off (e.g. auto-disabled earlier for being idle) would sit
        // untouched indefinitely, waiting for a human to notice and flip
        // the toggle. See GitHubActionsClient::enableIfDisabled.
        if ($github->enableIfDisabled()) {
            SystemEvent::log(
                'pm_agent.auto_enabled',
                "The PM Agent workflow was automatically re-enabled because epic \"{$epic->title}\" was just approved.",
                'system',
                'system',
            );
        }

        return new EpicResource($epic->fresh()->loadCount('tasks')->load('decidedBy'));
    }

    public function reject(Request $request, Epic $epic)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $epic->update([
            'status' => 'rejected',
            'decided_by' => $request->user()->id,
            'decided_at' => now(),
        ]);

        SystemEvent::log(
            'epic.rejected',
            "Epic \"{$epic->title}\" rejected by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['epic_id' => $epic->id],
        );

        return new EpicResource($epic->fresh()->loadCount('tasks')->load('decidedBy'));
    }

    public function delay(Request $request, Epic $epic)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $backOfTheLine = 1 + (int) Epic::where('status', 'proposed')->max('priority');

        $epic->update([
            'status' => 'proposed',
            'priority' => $backOfTheLine,
        ]);

        SystemEvent::log(
            'epic.delayed',
            "Epic \"{$epic->title}\" delayed to the end of the list by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['epic_id' => $epic->id],
        );

        return new EpicResource($epic->fresh()->loadCount('tasks')->load('decidedBy'));
    }
}
