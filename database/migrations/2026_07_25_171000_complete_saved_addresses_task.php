<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order')
            ->update([
                'status' => 'done',
                'commit_sha' => '992ed31fa24d4d039339187a81392fdb0f9d5c4b',
                'screenshot_path' => 'task-screenshots/saved-addresses-account-settings.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
