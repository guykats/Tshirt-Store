<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Epic Build-Status Rollup: Show Todo/In Progress/Blocked/Done Like the Board')->value('id');

        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Backend: compute a derived build_status on EpicResource for approved epics',
                'description' => "EpicResource (app/Http/Resources/EpicResource.php) currently only exposes the epic's own decision status (proposed/approved/rejected) plus a raw task_count from EpicController::index's withCount('tasks') - there's no visibility into whether an approved epic's linked project_tasks have actually started, stalled, or finished.\n\nAdd a `build_status` field to EpicResource, computed server-side (not a client-side guess) only for epics whose `status === 'approved'` (leave it null for proposed/rejected - a rollup only makes sense once there's something to roll up). Compute it from the epic's linked project_tasks with this precedence, mirroring the exact CASE ordering ProjectTaskController::index already uses for the Board's own row ordering (blocked beats in_progress beats todo beats done): any linked task status='blocked' => 'blocked'; else any status='in_progress' => 'in_progress'; else zero linked tasks OR all remaining tasks status='todo' => 'todo'; else all linked tasks status='done' => 'done'. Compute this in EpicController::index with a single aggregate query (e.g. selecting a MIN() over a CASE-mapped priority per task, joined/grouped by epic_id, using the same 'CASE status WHEN ... THEN n ... END' style already used elsewhere in this codebase for SQLite/MySQL-portable custom ordering - do not use MySQL-only functions like FIELD()) rather than N+1 queries per epic, and pass the result through to EpicResource (e.g. via \$epic->build_status set as a computed attribute, the same way task_count arrives via whenCounted). Also thread an optional `?build_status=` query param through EpicController::index (only meaningful combined with status=approved, mirroring how ProjectTaskController::index's `status` param already supports a synthetic 'approved' pseudo-value) so the frontend task building on this can offer a filter dropdown without pulling all epics client-side.\n\nWrite feature tests: an approved epic with a blocked linked task reads build_status='blocked' even if other linked tasks are done; an approved epic with all-done linked tasks reads 'done'; an approved epic with zero linked tasks reads 'todo'; a proposed/rejected epic's build_status is null regardless of any linked tasks; the `?build_status=` filter param returns only matching epics. Run `php artisan test` and confirm all existing Epic-related tests still pass unmodified (this is an additive field, not a breaking response shape change).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => $epicId,
                'title' => 'Frontend: mirror the Board tile-row + status filter pattern on Epics.jsx using build_status',
                'description' => "Depends on the epic's backend task ('Backend: compute a derived build_status on EpicResource for approved epics') having shipped first and landed the `build_status` field (null | 'blocked' | 'in_progress' | 'todo' | 'done') on each epic returned by GET /api/epics, plus its optional `?build_status=` query param - if that field isn't present yet on the API response, this task isn't unblocked, don't guess at the shape.\n\nresources/js/pages/Epics.jsx today only shows each epic's own proposed/approved/rejected badge (EPIC_STATUS_STYLES) and a raw task_count string - once approved, there's no at-a-glance signal of whether the epic is actually moving. resources/js/pages/ProjectProgress.jsx already has the exact UI vocabulary this needs: a STATUSES-driven grid of clickable stat tiles (counts per status, toggling a filter on click, active tile gets an accent border) directly above a `<select>` status filter, both using the same STATUS_STYLES color tokens (bg-blue-900/40 text-blue-300 for in_progress, bg-red-900/40 text-red-300 for blocked, bg-green-900/40 text-green-300 for done, a neutral white/[0.07] tint for todo) and the shared FOCUS_RING class already duplicated in both files. Mirror that same pattern on Epics.jsx, scoped to build_status specifically (this is a rollup one level up from the Board, not a replacement for it): add a small tile row + `<select>` filter for build_status, computing tile counts client-side from the loaded approved epics (or via the new `?build_status=` param, whichever keeps the component simplest given what the backend task actually shipped), and render each approved epic's build_status as a badge on its card using the same STATUS_STYLES color tokens as ProjectProgress.jsx (proposed/rejected epics keep showing exactly what they show today - untouched). The tile row and filter should only be visible when there's at least one approved epic in view, so the proposed-only default view isn't cluttered with an always-empty rollup.\n\nAdd both an English and real (not literal-translation) Hebrew i18n entry in resources/js/i18n/index.js for every new user-facing string (the existing epics_status_* and progress_status_* keys are useful references for tone/format). Take a Playwright screenshot showing the new tile row and at least one epic card with a visible build_status badge as evidence, per CLAUDE.md's verification bar.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Backend: compute a derived build_status on EpicResource for approved epics',
            'Frontend: mirror the Board tile-row + status filter pattern on Epics.jsx using build_status',
        ])->delete();
    }
};
