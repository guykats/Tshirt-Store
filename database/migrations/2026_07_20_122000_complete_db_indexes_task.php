<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Add database indexes on high-traffic query columns')
            ->update([
                'status' => 'done',
                'commit_sha' => 'baf8e0dab77a83f1645fb995141c5f771314bd08',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Add database indexes on high-traffic query columns')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
