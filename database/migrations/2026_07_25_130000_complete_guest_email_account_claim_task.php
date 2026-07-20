<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checking out as a guest permanently blocks that email from ever registering a real account')
            ->update([
                'status' => 'done',
                'commit_sha' => 'ac95bbc2c9cf2f182bbd8e30b9f5be756d17b7c1',
                'screenshot_path' => null,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checking out as a guest permanently blocks that email from ever registering a real account')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
