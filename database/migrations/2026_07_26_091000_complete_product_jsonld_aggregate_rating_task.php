<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product JSON-LD never gained the aggregateRating field now that reviews exist')
            ->update([
                'status' => 'done',
                'commit_sha' => 'b8d7b45998d52917be1b0692dfb8787910cf70d7',
                'screenshot_path' => 'task-screenshots/product-jsonld-aggregate-rating.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Product JSON-LD never gained the aggregateRating field now that reviews exist')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
