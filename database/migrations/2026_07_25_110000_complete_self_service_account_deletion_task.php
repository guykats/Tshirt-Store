<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Customers have no self-service way to delete their account or request their data be removed')
            ->update([
                'status' => 'done',
                'commit_sha' => '6485dac952d09aa662dc67dda7cd08614138239b',
                'screenshot_path' => 'task-screenshots/account-delete-danger-zone-en.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Customers have no self-service way to delete their account or request their data be removed')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
