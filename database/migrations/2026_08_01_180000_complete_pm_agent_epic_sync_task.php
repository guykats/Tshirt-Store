<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Fix the epics live-click-invisible-to-CI gap too',
            'description' => 'Direct follow-up to the project_tasks task-selection sync fix: epics have the identical problem (approve/reject/delay on the live Epics tab writes straight to production, invisible to pm-agent.yml\'s disposable local SQLite replay), plus a second problem project_tasks doesn\'t have - epics can be created live too, with no migration at all, via VisionerChatController::proposeEpic (the /dashboard/chat Visioner conversation). Fixed with GET /api/pm-agent-automation/epic-decisions (same token-gated X-PM-Agent-Token/PM_AGENT_BOARD_TOKEN pattern) exporting the real epics table from production, and a new php artisan pm-agent:sync-epic-decisions command (App\\Console\\Commands\\SyncEpicDecisions) that updates locally-known epics\' status/priority and inserts live-only ones before the PM Agent starts. Since a synced-in epic may have no real migration behind it, pm-agent.yml\'s prompt (step 3) now explicitly requires the PM Agent to push a migration inserting the epic itself before referencing its id in any project_tasks migration - otherwise the reference would dangle on the next fresh replay, since the sync-only local id only exists for one CI run. Updated .claude/skills/ship-project-task/SKILL.md and .claude/skills/propose-epics/SKILL.md to document this for interactive sessions too.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '37c172a5bfa97d0e44ce072a5625d17136d77f45',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fix the epics live-click-invisible-to-CI gap too')->delete();
    }
};
