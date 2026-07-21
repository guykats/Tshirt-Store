<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Add a persistent floating admin link to Team Management, visible site-wide')
            ->update([
                'status' => 'done',
                'commit_sha' => 'f1ca82fc819efedc3a1137c0b2ceb68759fd9a2a',
                'screenshot_path' => 'task-screenshots/floating-admin-link.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Add a persistent floating admin link to Team Management, visible site-wide')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
