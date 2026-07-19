<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Security response headers (CSP, HSTS, X-Frame-Options)')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Security response headers (CSP, HSTS, X-Frame-Options)')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
