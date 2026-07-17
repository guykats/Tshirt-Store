<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Brand story / About page')
            ->update([
                'status' => 'done',
                'commit_sha' => '2c66296c8815a42fe057f4e7db637d38aef934c9',
                'screenshot_path' => 'task-screenshots/about-page.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Brand story / About page')
            ->update(['status' => 'todo', 'commit_sha' => null, 'screenshot_path' => null, 'completed_at' => null]);
    }
};
