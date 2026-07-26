<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Login.jsx has no 'remember me' checkbox even though AuthController already honors the remember flag")
            ->update([
                'status' => 'done',
                'commit_sha' => '07d797f938abe7931a369bc26e641037ab9ba1a7',
                'screenshot_path' => 'task-screenshots/login-remember-me-checkbox.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Login.jsx has no 'remember me' checkbox even though AuthController already honors the remember flag")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
