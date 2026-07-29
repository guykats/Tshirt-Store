<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: split design_suggestions status into pending/kept/published and add a publish() action')
            ->update([
                'status' => 'done',
                'commit_sha' => '9f6a8d70f2a0b2e14e95e5c617fd62d3b0be9a76',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: split design_suggestions status into pending/kept/published and add a publish() action')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
