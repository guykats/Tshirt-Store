<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog and product-detail loading skeletons give screen-reader users no loading announcement')
            ->update([
                'status' => 'done',
                'commit_sha' => '3bcdc0f93a638b163fce501eb9552986e7c42d90',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Catalog and product-detail loading skeletons give screen-reader users no loading announcement')
            ->update(['status' => 'in_progress', 'commit_sha' => null, 'completed_at' => null, 'updated_at' => now()]);
    }
};
