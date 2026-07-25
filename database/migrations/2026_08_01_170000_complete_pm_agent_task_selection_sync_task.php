<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix the cron\'s real task-selection blind spot, not just the idle-check',
            'description' => 'Follow-up the owner asked for after the auto-disable-if-idle task: the idle-check fix only corrected whether the automation toggle turns itself off - the cron\'s actual "which task should I build" decision still relied entirely on pm-agent.yml\'s disposable local SQLite replay, which has no idea about a live "Approve for development" click on the real dashboard (that click writes straight to production MySQL; nothing else reads it). Fixed by adding GET /api/pm-agent-automation/approved-todo-titles (token-gated via the same X-PM-Agent-Token/PM_AGENT_BOARD_TOKEN as disable-if-idle) which exports the real set of approved todo task titles from production, and a new php artisan pm-agent:sync-approved-titles command (App\\Console\\Commands\\SyncApprovedTaskTitles) that fetches it and overwrites this CI run\'s freshly-seeded local project_tasks.approved_for_dev flags to match. Wired into pm-agent.yml as two new steps (migrate+seed, then sync) running before the PM Agent starts, with the prompt rewritten to stop telling Claude to re-run migrate:fresh --seed --force for board-checking purposes (that would wipe the sync - it is still correct to run it again later as part of verifying the actual code change). Also updated .claude/skills/ship-project-task/SKILL.md so an interactive session understands the same live/replay gap. Known remaining gap, documented in CLAUDE.md: epics have the identical live-click-invisible-to-CI problem (approve/reject/delay) and are not yet covered by an equivalent sync.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '6c10ff4feacb8d2e877cd46c150e93bcb6aa7cc8',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix the cron\'s real task-selection blind spot, not just the idle-check')->delete();
    }
};
