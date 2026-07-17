<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Open Graph / social share meta tags')
            ->update([
                'status' => 'done',
                'commit_sha' => 'cc67cf9db5fcf368ca58a90d6d77a1489009fe91',
                'screenshot_path' => 'task-screenshots/og-social-tags.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Open Graph / social share meta tags')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
