<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Customers have no self-service way to delete their account or request their data be removed')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Customers have no self-service way to delete their account or request their data be removed')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
