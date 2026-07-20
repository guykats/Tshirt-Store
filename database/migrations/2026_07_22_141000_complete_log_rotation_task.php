<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job')
            ->update([
                'status' => 'done',
                'commit_sha' => '3fa24b7cd496cb05fe730b14984be2fe05cd30df',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Production log file (storage/logs/laravel.log) has no rotation or retention, unlike the DB backup job')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
