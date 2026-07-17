<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AgentStatusResource;
use App\Models\AgentStatus;
use App\Models\SystemEvent;
use Illuminate\Http\Request;

class AgentStatusController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        return AgentStatusResource::collection(AgentStatus::orderBy('agent_name')->get());
    }

    public function update(Request $request, AgentStatus $agentStatus)
    {
        abort_unless($request->user()->isAdmin(), 403);

        // current_task is intentionally not accepted here anymore — it's derived live
        // from project_tasks in AgentStatusResource, so a manually-typed value here
        // would just get silently overridden on the next GET and mislead whoever set it.
        $data = $request->validate([
            'status' => ['required', 'in:idle,pending_approval,executing'],
        ]);

        $agentStatus->update($data);

        SystemEvent::log(
            'agent_status.updated',
            "{$agentStatus->agent_name} status set to {$agentStatus->status} by {$request->user()->name}.",
            $request->user()->name,
            'user',
            ['agent_status_id' => $agentStatus->id],
        );

        return new AgentStatusResource($agentStatus);
    }
}
