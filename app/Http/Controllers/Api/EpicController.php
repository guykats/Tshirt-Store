<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EpicResource;
use App\Models\Epic;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class EpicController extends Controller
{
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

        return EpicResource::collection($epics);
    }

    public function approve(Request $request, Epic $epic)
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
