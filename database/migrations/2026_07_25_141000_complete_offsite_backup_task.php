<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nightly database backups are written only to local disk on the same host they are meant to protect')
            ->update([
                'status' => 'done',
                'commit_sha' => '68269d9160d8a6071ae1330e1e3dde23a808e298',
                'screenshot_path' => null,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nightly database backups are written only to local disk on the same host they are meant to protect')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
