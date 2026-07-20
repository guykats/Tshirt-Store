<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No proactive alert when a variant\'s stock hits zero (or the low-stock threshold)')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
