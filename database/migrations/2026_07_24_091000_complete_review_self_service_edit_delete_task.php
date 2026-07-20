<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one')
            ->update([
                'status' => 'done',
                'commit_sha' => '9519253a516271949a983c83ea82025652f75444',
                'screenshot_path' => 'task-screenshots/review-self-service-edit-delete.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
