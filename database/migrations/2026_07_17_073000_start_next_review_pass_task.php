<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Marks a task in_progress the moment work actually starts, rather than only ever
     * inserting rows retroactively as "done" — otherwise the board never shows live
     * work and looks backdated rather than real-time.
     */
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'title' => 'Fourth review pass: remaining gaps',
            'description' => 'Continuing the security/correctness/UX review after three prior rounds found and fixed real bugs (overselling, webhook spoofing, rate limiting, deploy safety, etc). Looking for what\'s left.',
            'agent_name' => 'Dev Agent',
            'status' => 'in_progress',
            'task_type' => 'quality',
            'commit_sha' => null,
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Fourth review pass: remaining gaps')->delete();
    }
};
