<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Frontend: 'Discover' admin tab surfacing the nightly design suggestion batch")
            ->update([
                'status' => 'done',
                'commit_sha' => 'ed9f2d1ea6ef3d19931495386d6f649712517528',
                'screenshot_path' => 'task-screenshots/discover-admin-tab.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Frontend: 'Discover' admin tab surfacing the nightly design suggestion batch")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
