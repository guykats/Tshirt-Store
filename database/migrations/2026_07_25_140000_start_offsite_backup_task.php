<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nightly database backups are written only to local disk on the same host they are meant to protect')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Nightly database backups are written only to local disk on the same host they are meant to protect')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
