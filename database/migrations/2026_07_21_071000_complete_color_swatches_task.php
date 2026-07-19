<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Real color swatches on the product variant selector')
            ->update([
                'status' => 'done',
                'commit_sha' => '399718c6f6851b01f2e12338984d9aa447f2c156',
                'screenshot_path' => 'task-screenshots/product-color-swatches.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Real color swatches on the product variant selector')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
