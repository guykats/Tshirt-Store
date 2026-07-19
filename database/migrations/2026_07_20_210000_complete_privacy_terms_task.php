<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Privacy Policy & Terms of Service pages')
            ->update([
                'status' => 'done',
                'commit_sha' => 'd1931ab69eb679f38bb864fb6c7660fc1617d22e',
                'screenshot_path' => 'task-screenshots/privacy-policy-page.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Privacy Policy & Terms of Service pages')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
