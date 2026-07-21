<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'ProductDetail.jsx never handles a failed product fetch — infinite skeleton, not an error state')
            ->update([
                'status' => 'done',
                'commit_sha' => '3e9f44ec5bad410a7d587d4b0d66e645acd85681',
                'screenshot_path' => 'task-screenshots/product-detail-fetch-error.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'ProductDetail.jsx never handles a failed product fetch — infinite skeleton, not an error state')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
