<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A failed nightly database backup is only visible in the admin audit log — nobody is actively notified')
            ->update([
                'status' => 'done',
                'commit_sha' => '44175059a68bec8f9d598a9de35b6356459112f3',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A failed nightly database backup is only visible in the admin audit log — nobody is actively notified')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
