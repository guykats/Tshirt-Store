<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling or refunding an order never restores the reserved stock it decremented')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Cancelling or refunding an order never restores the reserved stock it decremented')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
