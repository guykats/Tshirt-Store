<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog and product-detail loading skeletons give screen-reader users no loading announcement')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog and product-detail loading skeletons give screen-reader users no loading announcement')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
