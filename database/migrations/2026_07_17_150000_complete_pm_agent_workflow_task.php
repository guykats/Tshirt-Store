<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Autonomous PM Agent (GitHub Action, 30min cron)',
            'description' => 'Added .github/workflows/pm-agent.yml — runs Claude Code unattended every 30 minutes via anthropics/claude-code-action@v1, independent of any interactive session. Each run reads CLAUDE.md, syncs with project_tasks/epics, ships or seeds real verified work, and pushes to main. Capped at --max-turns 30 / 20min timeout (moderate, owner-chosen budget). Blocked on the owner adding an ANTHROPIC_API_KEY repo secret before it can actually fire.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'infra',
            'status' => 'done',
            'commit_sha' => 'edeadcdb4ca34584fbc8ff21cd56ef20b2c6f44a',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Autonomous PM Agent (GitHub Action, 30min cron)')->delete();
    }
};
