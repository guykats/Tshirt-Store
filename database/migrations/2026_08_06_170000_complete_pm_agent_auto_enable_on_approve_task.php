<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix real bug: approving work never re-enabled a disabled PM Agent workflow',
            'description' => 'Owner reported approving an epic and nothing happening for hours. Confirmed from real GitHub Actions run history: run #157 fired on schedule at 17:57 UTC, then nothing for 20h53m until #158, a manual workflow_dispatch, with the workflow\'s enable-timestamp matching seconds before it - meaning a human had to manually flip the toggle. Root cause: a disabled workflow can never fire on its own schedule to notice new approved work exists, so the earlier auto-disable-when-idle fix (see the disable-if-idle epic-awareness fix) had no counterpart - once off, it stayed off no matter what got approved afterward, until a human happened to notice and manually re-enable it. Fixed by adding GitHubActionsClient::enableIfDisabled() (re-enables only if currently disabled; a safe no-op if already enabled, not configured, or on any upstream error - never blocks the approval itself) and calling it from both EpicController::approve and ProjectTaskController::approve, logging a pm_agent.auto_enabled SystemEvent whenever it actually flips the toggle. Test coverage: both controllers\' approve endpoints re-enable a disabled workflow and leave an already-enabled one alone.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'bugfix',
            'requested_by' => 'Guy',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '951f5a8439a4fb00c9855cc202302d063015bc80',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix real bug: approving work never re-enabled a disabled PM Agent workflow')->delete();
    }
};
