<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Shipment tracking number and carrier on orders')
            ->update([
                'status' => 'done',
                'commit_sha' => 'a9e153c3c4382016e109e7ce91da2050470f5dbe',
                'screenshot_path' => 'task-screenshots/order-tracking-info.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Shipment tracking number and carrier on orders')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
