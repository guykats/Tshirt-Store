<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Accessibility pass')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c2e271e7156fb434d0d352190f0f2a3789b8a51a',
                'screenshot_path' => 'task-screenshots/accessibility-pass.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Accessibility pass')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
