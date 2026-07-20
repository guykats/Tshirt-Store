<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
