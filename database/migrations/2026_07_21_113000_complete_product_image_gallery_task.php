<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product image gallery (multiple images per product)')
            ->update([
                'status' => 'done',
                'commit_sha' => 'c07272829e283ed04bb287e238c8851b037f9627',
                'screenshot_path' => 'task-screenshots/product-image-gallery.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product image gallery (multiple images per product)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
