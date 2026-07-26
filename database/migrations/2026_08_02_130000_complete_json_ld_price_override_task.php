<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override")
            ->update([
                'status' => 'done',
                'commit_sha' => 'afb960332dd3010e000afa48d3795edc19e92986',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
