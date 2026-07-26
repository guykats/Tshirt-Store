<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language")
            ->update([
                'status' => 'done',
                'commit_sha' => '1855de68b2062466276532667fe8d93731ec91f4',
                'screenshot_path' => 'task-screenshots/audit-log-hebrew-dates.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language")
            ->update([
                'status' => 'todo',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
