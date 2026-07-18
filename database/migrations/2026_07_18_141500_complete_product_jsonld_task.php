<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product structured data (JSON-LD)')
            ->update([
                'status' => 'done',
                'commit_sha' => '25f557328337c845d1aa6f6211b202bdb395bf12',
                'screenshot_path' => 'task-screenshots/product-jsonld.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product structured data (JSON-LD)')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
