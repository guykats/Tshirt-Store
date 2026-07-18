<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Implement investor-ready homepage redesign')
            ->update([
                'status' => 'done',
                'commit_sha' => '29f45b1f03e1060b1fbca2e1afff696c63a2a51c',
                'screenshot_path' => 'task-screenshots/investor-homepage-redesign.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Implement investor-ready homepage redesign')
            ->update([
                'status' => 'todo',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
