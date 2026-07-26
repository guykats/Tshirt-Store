<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "The PDF invoice's Payment Status value is never translated for Hebrew-locale invoices")
            ->update([
                'status' => 'done',
                'commit_sha' => 'a4ad17c30107eb7f7b24df2c7b5509ec044c970c',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "The PDF invoice's Payment Status value is never translated for Hebrew-locale invoices")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
