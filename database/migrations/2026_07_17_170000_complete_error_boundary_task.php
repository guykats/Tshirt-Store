<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Global error boundary')
            ->update([
                'status' => 'done',
                'commit_sha' => 'cb099ea02b81c9f99ba664436291d0c2d7bdfe2c',
                'screenshot_path' => 'task-screenshots/error-boundary.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Global error boundary')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
