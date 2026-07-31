<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('epics')->insert([
            'title' => 'Epic Build-Status Rollup: Show Todo/In Progress/Blocked/Done Like the Board',
            'description' => "Grounded in the actual code: today Epics.jsx only ever shows the epic's own decision status (proposed/approved/rejected/rejected-styled amber/green/red badge) plus a raw task_count (EpicController::index already does withCount('tasks'), EpicResource exposes it) - once an epic is approved, the tab gives no visibility into whether its breakdown tasks have actually started, stalled, or finished. The Board tab (ProjectProgress.jsx) already has the exact vocabulary and UI pattern this needs: clickable stat tiles + a status filter dropdown for todo/in_progress/blocked/done (plus the 'approved' pseudo-status ProjectTaskController::index already computes). This epic asks for the same rollup applied one level up, from an epic's linked project_tasks. For an approved epic specifically (proposed/rejected epics keep showing their own real status as today - a rollup only makes sense once there's something to roll up), compute a derived build status from its linked tasks with a sensible precedence (mirroring the same CASE ordering ProjectTaskController::index already uses for the Board's own row ordering: blocked beats in_progress beats todo beats done) - e.g. any linked task blocked => epic reads Blocked; else any in_progress => In Progress; else zero linked tasks, or all still todo => Approved (awaiting build); else all linked tasks done => Done. Surface this as a real backend-computed field (extending EpicResource/EpicController, not a client-side guess), and mirror the Board's tile-row + status filter UI pattern on Epics.jsx so the owner can see at a glance, without opening the Board separately, which approved epics are actually moving versus stuck versus finished.",
            'agent_name' => 'Visioner Agent',
            'status' => 'proposed',
            'priority' => 0,
            'decided_by' => null,
            'decided_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('epics')->where('title', 'Epic Build-Status Rollup: Show Todo/In Progress/Blocked/Done Like the Board')->delete();
    }
};
