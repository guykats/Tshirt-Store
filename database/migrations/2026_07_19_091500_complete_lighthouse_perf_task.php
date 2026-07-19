<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Mobile-first performance pass (Lighthouse)')
            ->update([
                'status' => 'done',
                'commit_sha' => 'ecad2f69bc0edc664d1f34c545ec780007285abc',
                'screenshot_path' => 'task-screenshots/lighthouse-mobile-perf.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Mobile-first performance pass (Lighthouse)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
