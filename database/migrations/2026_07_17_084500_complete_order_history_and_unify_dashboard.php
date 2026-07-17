<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $sha = '18ab50143fef5293e9cbd4bd7450e6df4d65dc68';

        DB::table('project_tasks')
            ->where('title', 'Customer order history page')
            ->update([
                'status' => 'done',
                'commit_sha' => $sha,
                'screenshot_path' => 'task-screenshots/customer-order-history.png',
                'completed_at' => $now,
                'updated_at' => $now,
            ]);

        DB::table('project_tasks')->insert([
            'title' => 'Unify Agent Status with the live progress board',
            'description' => 'The site-ops dashboard\'s Agent Status table had its own manually-typed current_task field, disconnected from the project_tasks board. Now derived live from each agent\'s actual in-progress/most-recent task, plus a backlog count and a progress summary strip on the main dashboard.',
            'agent_name' => 'Dev Agent',
            'status' => 'done',
            'task_type' => 'feature',
            'commit_sha' => $sha,
            'screenshot_path' => 'task-screenshots/unified-agent-dashboard.png',
            'blocked_reason' => null,
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Unify Agent Status with the live progress board')->delete();
    }
};
