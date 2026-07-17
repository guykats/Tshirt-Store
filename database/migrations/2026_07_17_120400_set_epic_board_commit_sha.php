<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Epic approval board (Visioner Agent)')
            ->update([
                'commit_sha' => 'f9ebeb4c33f379be76adfcb692eb3a2ad2afeb63',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Epic approval board (Visioner Agent)')
            ->update(['commit_sha' => null]);
    }
};
