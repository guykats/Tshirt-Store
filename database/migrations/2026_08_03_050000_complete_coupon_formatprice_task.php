<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'CouponManagement.jsx hardcodes a literal "$" for fixed-value coupons instead of the app\'s own locale-aware currency formatter')
            ->update([
                'status' => 'done',
                'commit_sha' => '9ce1bb2caf251fe17e1a64a25a050f66b28e2681',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'CouponManagement.jsx hardcodes a literal "$" for fixed-value coupons instead of the app\'s own locale-aware currency formatter')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
