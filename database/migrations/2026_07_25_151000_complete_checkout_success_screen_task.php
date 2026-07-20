<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step')
            ->update([
                'status' => 'done',
                'commit_sha' => '60da52d7022c922101b07a51c6bba2842e9f6063',
                'screenshot_path' => 'task-screenshots/checkout-success-en.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
