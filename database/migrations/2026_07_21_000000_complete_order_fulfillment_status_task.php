<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Order fulfillment status progression + shipment emails')
            ->update([
                'status' => 'done',
                'commit_sha' => 'f0090cef3a39b4eb7c7471c0cae540464700a9fc',
                'screenshot_path' => 'task-screenshots/order-fulfillment-status.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Order fulfillment status progression + shipment emails')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
