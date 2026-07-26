<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Four more admin dashboard pages render timestamps in fixed English formatting regardless of site language")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
