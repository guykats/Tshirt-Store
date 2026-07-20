<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
