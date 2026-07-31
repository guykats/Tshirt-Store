<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Source and seed real photographic imagery for the existing catalog')
            ->update([
                'status' => 'done',
                'commit_sha' => '2a3804d300c1e68862f9139f16b4a0dbe74da165',
                'screenshot_path' => 'task-screenshots/photographic-catalog-imagery.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Source and seed real photographic imagery for the existing catalog')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
