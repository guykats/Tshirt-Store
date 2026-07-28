<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix real bug: disable-if-idle ignored approved epics awaiting breakdown',
            'description' => 'Owner reported that enabling the automation toggle right after approving an epic got it disabled again on the next run, every time. Root cause: PmAgentAutomationController::disableIfIdle runs BEFORE the "Run PM Agent" step and only checked project_tasks where status=todo AND approved_for_dev=1. An approved epic with zero linked project_tasks yet is real pending work - the PM Agent is about to turn it into approved tasks in that very run (per the recent auto-approve-epic-derived-tasks change) - but the idle-check ran first and saw nothing yet, so it disabled the workflow before the epic ever got a chance to be broken down. Fixed by adding hasApprovedEpicAwaitingBreakdown() (Epic::where(status=approved)->whereDoesntHave(tasks)->exists()) as a second not-idle condition, with test coverage for both the awaiting-breakdown case (stays enabled) and the already-broken-down case (still disables normally, no regression).',
            'agent_name' => 'Dev Agent',
            'task_type' => 'bugfix',
            'requested_by' => 'Guy',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '66e0c4224b18b114c332f9cdcf85047f21489aa7',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix real bug: disable-if-idle ignored approved epics awaiting breakdown')->delete();
    }
};
