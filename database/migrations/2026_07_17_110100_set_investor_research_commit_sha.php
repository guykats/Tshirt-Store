<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Investor-readiness research + homepage concept mockup')
            ->update([
                'commit_sha' => '2ba442dd379041fe5f370d0155ab677950edfda6',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Investor-readiness research + homepage concept mockup')
            ->update(['commit_sha' => null]);
    }
};
