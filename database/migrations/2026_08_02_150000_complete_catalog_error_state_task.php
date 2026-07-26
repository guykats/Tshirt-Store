<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog.jsx conflates a failed product fetch with a genuinely empty catalog')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c80db7ba95a0a3e35d3c198cd12fd3f31fb5753a',
                'screenshot_path' => 'task-screenshots/catalog-fetch-error.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog.jsx conflates a failed product fetch with a genuinely empty catalog')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
