<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order queues have no way to search or filter by customer name or email')
            ->update([
                'status' => 'done',
                'commit_sha' => '51e89bb51ea09827a91257c7a091b1be1308e754',
                'screenshot_path' => 'task-screenshots/admin-order-search.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order queues have no way to search or filter by customer name or email')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
