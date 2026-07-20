<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Dashboard admin queues silently miss orders/designs beyond page 1')
            ->update([
                'status' => 'done',
                'commit_sha' => '4419256a2530039916cd37cf7c356e66f0001d50',
                'screenshot_path' => 'task-screenshots/dashboard-admin-queue-pagination.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Dashboard admin queues silently miss orders/designs beyond page 1')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
