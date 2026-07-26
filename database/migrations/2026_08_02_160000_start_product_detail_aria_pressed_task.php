<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
