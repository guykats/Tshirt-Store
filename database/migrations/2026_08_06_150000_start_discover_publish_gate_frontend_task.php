<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
