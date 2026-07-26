<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
