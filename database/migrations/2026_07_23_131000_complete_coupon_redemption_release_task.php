<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed')
            ->update([
                'status' => 'done',
                'commit_sha' => 'f69249dfc97dbb4af2d3b66cb3cdd49596238d15',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
