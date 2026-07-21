<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones')
            ->update([
                'status' => 'done',
                'commit_sha' => 'e9fc37524126600b10262330b198ef1856627eae',
                'screenshot_path' => 'task-screenshots/sidebar-store-settings-system-zones.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Status transition only; no reversible structural change.
    }
};
