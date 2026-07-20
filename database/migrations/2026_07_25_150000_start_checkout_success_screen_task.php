<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
