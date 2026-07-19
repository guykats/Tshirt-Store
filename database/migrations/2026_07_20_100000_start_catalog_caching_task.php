<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Caching layer for catalog listings')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Caching layer for catalog listings')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
