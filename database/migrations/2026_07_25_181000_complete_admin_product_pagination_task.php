<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin product management silently drops products past page 1')
            ->update([
                'status' => 'done',
                'commit_sha' => '5f2c32f5297e34e61a2091d6432645fd5942fa12',
                'screenshot_path' => 'task-screenshots/admin-product-management-pagination.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin product management silently drops products past page 1')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
