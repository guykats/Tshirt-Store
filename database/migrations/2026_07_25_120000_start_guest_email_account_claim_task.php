<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checking out as a guest permanently blocks that email from ever registering a real account')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checking out as a guest permanently blocks that email from ever registering a real account')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
