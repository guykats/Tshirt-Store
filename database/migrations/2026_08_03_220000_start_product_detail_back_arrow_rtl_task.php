<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
