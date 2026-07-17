<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Epic approval board (Visioner Agent)',
            'description' => 'New Epics section on /dashboard/progress: the Visioner Agent proposes strategic initiatives (epics table, linked to project_tasks via epic_id), and an admin can Choose (approve), Reject, or Delay each one to the back of the list. Seeded with 6 real candidate epics for review.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'commit_sha' => null,
            'screenshot_path' => 'task-screenshots/epic-approval-board.png',
            'blocked_reason' => null,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Epic approval board (Visioner Agent)')->delete();
    }
};
