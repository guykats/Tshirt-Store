<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Zero-context onboarding docs (CLAUDE.md + history)',
            'description' => 'Added CLAUDE.md (operational playbook: standing agreement with the project owner, every hard-won convention/gotcha, the agent/skill/board system, the verification bar) and docs/PROJECT-HISTORY.md (narrative record of what was built, in what order, and why). Updated README.md\'s stale "Agent Status" paragraph and pointed it at both. Goal: a brand-new session with zero prior context can pick this project up correctly without a human re-explaining it.',
            'agent_name' => 'Ops Agent',
            'task_type' => 'infra',
            'status' => 'done',
            'commit_sha' => '47d64bf49d276cab22b45261ef6995cde8695979',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Zero-context onboarding docs (CLAUDE.md + history)')->delete();
    }
};
