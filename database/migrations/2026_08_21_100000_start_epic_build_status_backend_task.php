<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: compute a derived build_status on EpicResource for approved epics')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: compute a derived build_status on EpicResource for approved epics')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
