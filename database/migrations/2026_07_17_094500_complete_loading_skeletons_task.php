<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Loading skeleton states')
            ->update([
                'status' => 'done',
                'commit_sha' => '8d4b8a2401828f45ce5a5c4182a306cb9688987c',
                'screenshot_path' => 'task-screenshots/loading-skeletons.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Loading skeleton states')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
