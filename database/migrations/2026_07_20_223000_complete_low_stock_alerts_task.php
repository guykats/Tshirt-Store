<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Low-stock inventory alerts for admins')
            ->update([
                'status' => 'done',
                'commit_sha' => 'fc6ba652e872c0c636e121177f83dc502ad55fcc',
                'screenshot_path' => 'task-screenshots/low-stock-alerts.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Low-stock inventory alerts for admins')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
