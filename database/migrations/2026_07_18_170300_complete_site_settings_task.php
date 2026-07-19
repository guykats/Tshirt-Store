<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Site Design Configuration panel (admin)')
            ->update([
                'status' => 'done',
                'commit_sha' => '1a7623b9dff5f2d3982270cfa766114466758ef5',
                'screenshot_path' => 'task-screenshots/site-design-settings.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Site Design Configuration panel (admin)')
            ->update([
                'status' => 'todo',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
