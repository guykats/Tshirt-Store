<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways')
            ->update([
                'status' => 'done',
                'commit_sha' => 'a4109174bcc37c5f9c3a29e9c1d08899bb309199',
                'screenshot_path' => 'task-screenshots/catalog-depth-14-products.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
