<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Render real photo URLs as actual images instead of silently falling back to Star-of-David art')
            ->update([
                'status' => 'done',
                'commit_sha' => 'ff7d0048b38f7ffece2949c69ab62ee56b21e066',
                'screenshot_path' => 'task-screenshots/garment-mockup-real-photo.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Render real photo URLs as actual images instead of silently falling back to Star-of-David art')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
