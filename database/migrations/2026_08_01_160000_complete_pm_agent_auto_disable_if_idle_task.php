<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Auto-disable PM Agent automation when nothing is approved to build',
            'description' => 'The owner asked: if the toggle is enabled but no project_tasks row is currently status=todo AND approved_for_dev=1, turn the automation off automatically. Building this surfaced a real gap first: pm-agent.yml (and any agent following ship-project-task step 0) only ever sees a disposable local SQLite rebuilt from migration history - it has no live connection to production MySQL, so a human clicking "Approve for development" on the real dashboard is invisible to it. Fixed with a new token-gated (X-PM-Agent-Token header, not Sanctum) production endpoint, POST /api/pm-agent-automation/disable-if-idle (PmAgentAutomationController::disableIfIdle), which checks the REAL approved_for_dev state and calls GitHubActionsClient::disable() + logs a real SystemEvent (pm_agent.auto_disabled) server-side if idle. pm-agent.yml calls it via curl on every run, authenticated with the PM_AGENT_BOARD_TOKEN secret (must be set identically in production .env and as a GitHub Actions repo secret - harmless no-op until then, same deferred-credential pattern as the others). Scope note left in CLAUDE.md: this only fixes the idle-check: the cron\'s actual task-selection logic still relies on the migrate-fresh-and-replay view for deciding which task to build, a known, separately-flagged remaining gap.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => '90a1c1f60cd1bb5a6b648825e0dfef779bbc8a3c',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Auto-disable PM Agent automation when nothing is approved to build')->delete();
    }
};
