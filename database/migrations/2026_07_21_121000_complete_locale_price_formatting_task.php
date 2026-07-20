<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Locale-aware price and number formatting for Hebrew')
            ->update([
                'status' => 'done',
                'commit_sha' => '60f145c5f0b362254dfadd3d6bf82b4a048bddc9',
                'screenshot_path' => 'task-screenshots/locale-price-formatting.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Locale-aware price and number formatting for Hebrew')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
