<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Checkout and account address forms hardcode country: US with no country field ever rendered")
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Checkout and account address forms hardcode country: US with no country field ever rendered")
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
