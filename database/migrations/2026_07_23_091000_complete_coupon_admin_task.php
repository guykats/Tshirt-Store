<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists')
            ->update([
                'status' => 'done',
                'commit_sha' => '193916c9250a73eaa0b6425845f1ec08de173a0e',
                'screenshot_path' => 'task-screenshots/coupon-admin-management.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
