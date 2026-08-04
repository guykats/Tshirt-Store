<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: mirror the Board tile-row + status filter pattern on Epics.jsx using build_status')
            ->update([
                'status' => 'done',
                'commit_sha' => 'be0813599e5b840b7592edc5b6df145af39a03fd',
                'screenshot_path' => 'task-screenshots/epics-build-status-rollup.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: mirror the Board tile-row + status filter pattern on Epics.jsx using build_status')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
