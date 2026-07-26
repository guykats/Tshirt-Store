<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'CouponManagement.jsx renders coupon expiry dates in a fixed English format regardless of site language')
            ->update([
                'status' => 'done',
                'commit_sha' => 'a897d2c7c647d2813b6951b5601263a4b063229e',
                'screenshot_path' => 'task-screenshots/coupon-expiry-hebrew-locale.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'CouponManagement.jsx renders coupon expiry dates in a fixed English format regardless of site language')
            ->update([
                'status' => 'todo',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
