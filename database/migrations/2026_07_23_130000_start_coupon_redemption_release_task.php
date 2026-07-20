<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
