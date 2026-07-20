<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
