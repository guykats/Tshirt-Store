<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)')
            ->update([
                'status' => 'done',
                'commit_sha' => '9ba324117cc2563bc0c3dc6419a05ca270a52cd0',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
