<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Every shared page URL renders the same static homepage Open Graph preview — no per-product image/title when a product link is shared')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Every shared page URL renders the same static homepage Open Graph preview — no per-product image/title when a product link is shared')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
