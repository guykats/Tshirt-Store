<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color")
            ->update([
                'status' => 'done',
                'commit_sha' => '7bb0bb7dacace0d36b8f73a272fcdabb8236642e',
                'screenshot_path' => 'task-screenshots/garment-mockup-color-fix.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
