<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them')
            ->update([
                'status' => 'done',
                'commit_sha' => 'fca0f78bcdce7a63991278c62b991cbef592d849',
                'screenshot_path' => 'task-screenshots/admin-order-search-actions.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
