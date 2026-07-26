<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "About.jsx's Star-of-David hero art is the only primary page illustration in the app with no accessible label")
            ->update([
                'status' => 'done',
                'commit_sha' => 'f0d62466e641a308fef0c8cb280c1ddaff448e4d',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "About.jsx's Star-of-David hero art is the only primary page illustration in the app with no accessible label")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
