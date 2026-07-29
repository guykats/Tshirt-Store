<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c5870e5cdad4ea6ea299f0d6d4d1035440a0d056',
                'screenshot_path' => 'task-screenshots/discover-tab-redesign-2026-07-29.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
