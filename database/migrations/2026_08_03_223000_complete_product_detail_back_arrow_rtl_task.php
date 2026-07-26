<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL")
            ->update([
                'status' => 'done',
                'commit_sha' => '5f1b594e89a479d8ff329c41372199c1e11e32db',
                'screenshot_path' => 'task-screenshots/product-detail-back-arrow-rtl.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "ProductDetail.jsx's 'back to catalog' link uses a hardcoded left-arrow glyph that doesn't mirror for Hebrew/RTL")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
