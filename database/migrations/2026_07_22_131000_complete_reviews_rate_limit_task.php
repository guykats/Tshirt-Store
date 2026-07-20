<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints')
            ->update([
                'status' => 'done',
                'commit_sha' => '07cdebf602b6e69bbcda8db3924a2f6fb9350b15',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'POST/DELETE /api/products/{product}/reviews have no rate limiting despite being public-write endpoints')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
