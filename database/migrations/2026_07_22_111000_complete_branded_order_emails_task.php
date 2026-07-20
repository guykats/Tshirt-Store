<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Transactional order emails are unbranded plain Blade tables')
            ->update([
                'status' => 'done',
                'commit_sha' => 'a6f8bbb9e3189cabc63c622cbd41e2881f31c0cc',
                'screenshot_path' => 'task-screenshots/branded-order-emails.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Transactional order emails are unbranded plain Blade tables')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
