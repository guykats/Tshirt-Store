<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
