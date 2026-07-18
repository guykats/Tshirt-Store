<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog search / filter')
            ->update([
                'status' => 'done',
                'commit_sha' => '058ac626dfd7ba053532adfa979f200bb82fe6e7',
                'screenshot_path' => 'task-screenshots/catalog-search.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog search / filter')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
