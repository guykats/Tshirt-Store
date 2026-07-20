<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one')
            ->update(['status' => 'in_progress', 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one')
            ->update(['status' => 'todo', 'updated_at' => now()]);
    }
};
