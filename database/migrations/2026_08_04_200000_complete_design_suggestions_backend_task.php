<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: nightly design_suggestions generation, promotion pipeline, and on-demand Discover batch endpoints')
            ->update([
                'status' => 'done',
                'commit_sha' => '0a5dff001abfc88a0d87eb4add7f09ece5d0c94f',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: nightly design_suggestions generation, promotion pipeline, and on-demand Discover batch endpoints')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
