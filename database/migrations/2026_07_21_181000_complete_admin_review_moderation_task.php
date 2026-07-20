<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin review moderation (delete abusive/fake reviews)')
            ->update([
                'status' => 'done',
                'commit_sha' => '330c0d771d74a67bdce025d5837992523cb8d917',
                'screenshot_path' => 'task-screenshots/admin-review-moderation.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin review moderation (delete abusive/fake reviews)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
