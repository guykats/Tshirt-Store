<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
