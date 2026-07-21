<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
