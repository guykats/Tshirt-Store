<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Coupon codes have no per-customer redemption cap, only a global one')
            ->update([
                'status' => 'done',
                'commit_sha' => 'e66a7f8f4bd3da7ec9d12ed0db07dde932c28ea6',
                'screenshot_path' => 'task-screenshots/coupon-per-customer-cap.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Coupon codes have no per-customer redemption cap, only a global one')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
