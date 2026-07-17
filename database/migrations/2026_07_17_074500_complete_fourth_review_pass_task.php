<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Fourth review pass: remaining gaps')
            ->update([
                'status' => 'done',
                'commit_sha' => '66961ac9a5227f950c4751577a7e9fb8277b571c',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Fourth review pass: remaining gaps')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null]);
    }
};
