<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Password-reset and low-stock-alert emails still render in Laravel\'s stock blue-button theme, clashing with the newly-branded order emails')
            ->update([
                'status' => 'done',
                'commit_sha' => '6dc01ec382cb1ddf868aab3f8185f4f7307fdff0',
                'screenshot_path' => 'task-screenshots/branded-reset-password-low-stock-emails.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Password-reset and low-stock-alert emails still render in Laravel\'s stock blue-button theme, clashing with the newly-branded order emails')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
