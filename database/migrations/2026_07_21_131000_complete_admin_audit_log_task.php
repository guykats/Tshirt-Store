<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin audit-log page with filtering and pagination')
            ->update([
                'status' => 'done',
                'commit_sha' => '88114ae5dc37337236752dc740494e892632c716',
                'screenshot_path' => 'task-screenshots/audit-log-admin-filters-pagination.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin audit-log page with filtering and pagination')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
