<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout (account-optional purchase flow)')
            ->update([
                'status' => 'done',
                'commit_sha' => 'dd3dfe3f9f4a39e89bd128185135ae29eb624dd3',
                'screenshot_path' => 'task-screenshots/guest-checkout.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout (account-optional purchase flow)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
