<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout has no way to look up an order after the browser session ends')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Guest checkout has no way to look up an order after the browser session ends')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
