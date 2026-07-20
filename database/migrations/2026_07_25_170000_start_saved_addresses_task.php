<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
