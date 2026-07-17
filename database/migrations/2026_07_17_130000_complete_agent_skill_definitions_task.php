<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Dedicated agent + skill definitions for board roles',
            'description' => 'Added .claude/agents/{dev,creative,ops,visioner}-agent.md so the agent_name labels used on the project_tasks and epics boards are real, invokable Claude Code subagents, plus two shared skills — ship-project-task (start/build/verify/complete/push procedure) and propose-epics (Visioner Agent research + seeding procedure) — that encode this repo\'s working conventions instead of leaving them implicit per-session.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'infra',
            'status' => 'done',
            'commit_sha' => 'ce2159de4282187eb675bad69a2c6fb7efa3f0df',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Dedicated agent + skill definitions for board roles')->delete();
    }
};
