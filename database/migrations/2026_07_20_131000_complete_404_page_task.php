<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', '404 / not-found page for unmatched SPA routes')
            ->update([
                'status' => 'done',
                'commit_sha' => '4437c8c3bd4742d9e1a0c7e5674914dc8ac9a073',
                'screenshot_path' => 'task-screenshots/404-not-found-page.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', '404 / not-found page for unmatched SPA routes')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
