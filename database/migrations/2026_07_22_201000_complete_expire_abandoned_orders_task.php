<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job')
            ->update([
                'status' => 'done',
                'commit_sha' => '3ff2638ba46f7e8a562222b5488ce82c3009389f',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
