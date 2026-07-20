<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
