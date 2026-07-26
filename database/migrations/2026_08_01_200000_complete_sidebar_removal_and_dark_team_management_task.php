<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Remove sidebars sitewide; redesign Team Management as a distinct dark-mode section')
            ->update([
                'status' => 'done',
                'commit_sha' => '5ca13230a5000887fac53bfa980517103acf9f82',
                'screenshot_path' => 'task-screenshots/team-management-board-dark.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Remove sidebars sitewide; redesign Team Management as a distinct dark-mode section')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
