<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'change-password and self-service account-deletion endpoints have no rate limiting despite re-checking a password')
            ->update([
                'status' => 'done',
                'commit_sha' => '698e9f8ff0978688cde00b8825c955d4010b0d67',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'change-password and self-service account-deletion endpoints have no rate limiting despite re-checking a password')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
