<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Build project progress dashboard')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c619c214395a579428ac75146b4db61026198a0',
                'screenshot_path' => 'task-screenshots/project-progress-dashboard.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Build project progress dashboard')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
