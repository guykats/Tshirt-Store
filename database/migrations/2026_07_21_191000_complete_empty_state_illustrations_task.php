<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Illustrated empty states for Wishlist, Orders, and Catalog no-results')
            ->update([
                'status' => 'done',
                'commit_sha' => '2ccf111659ac9e0153b110444e65efd2c30c5dba',
                'screenshot_path' => 'task-screenshots/wishlist-empty-state.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Illustrated empty states for Wishlist, Orders, and Catalog no-results')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
