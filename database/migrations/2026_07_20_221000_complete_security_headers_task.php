<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Security response headers (CSP, HSTS, X-Frame-Options)')
            ->update([
                'status' => 'done',
                'commit_sha' => '068e96e2d4fe8ef3dc2cca2cf63bbe4647dfb15a',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Security response headers (CSP, HSTS, X-Frame-Options)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
