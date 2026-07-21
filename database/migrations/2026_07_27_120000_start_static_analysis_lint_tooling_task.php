<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No static analysis/lint tooling configured for either half of the stack')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'No static analysis/lint tooling configured for either half of the stack')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
