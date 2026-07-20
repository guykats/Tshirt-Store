<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout has no way to look up an order after the browser session ends')
            ->update([
                'status' => 'done',
                'commit_sha' => 'df3c17d3c4621d6cadf4a34234cb9ecb6af64e14',
                'screenshot_path' => 'task-screenshots/guest-order-lookup-result.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout has no way to look up an order after the browser session ends')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
