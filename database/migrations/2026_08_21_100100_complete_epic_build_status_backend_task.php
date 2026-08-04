<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: compute a derived build_status on EpicResource for approved epics')
            ->update([
                'status' => 'done',
                'commit_sha' => '04c1eb4ecdfea209a61514a325ec2db8d98032a0',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Backend: compute a derived build_status on EpicResource for approved epics')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
