<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Auto-refresh the Board page while PM Agent automation is enabled',
            'description' => 'The owner asked why an in-progress PM Agent run never showed up live on the Board (/dashboard/progress) - the task list was only ever fetched once on mount/filter-change, so a task moving to in_progress/done on the server was invisible until a manual reload. Added polling (every 10s) to ProjectProgress.jsx, but scoped it to only run while the PM Agent automation toggle is enabled - if it is off, nothing is going to change on the board from the cron, so there is nothing worth polling for. The automation status itself is always polled (independent of its own value) since that is the only way to notice the toggle flipping (e.g. the auto-disable-if-idle logic turning it off server-side).',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => 'b0ade781e90ef41093d361fc2635a746046cc461',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Auto-refresh the Board page while PM Agent automation is enabled')->delete();
    }
};
