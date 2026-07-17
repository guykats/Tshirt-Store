<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Wire up catalog pagination controls')
            ->update([
                'status' => 'done',
                'commit_sha' => '2d78d4518881270fa25036a60a98e19d38ee3ce8',
                'screenshot_path' => 'task-screenshots/catalog-pagination.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Wire up catalog pagination controls')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
