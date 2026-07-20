<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'public/favicon.ico is a 0-byte empty file')
            ->update([
                'status' => 'done',
                'commit_sha' => 'ed146f84aee258fdef0fcce5f0696d4b1d9dffda',
                'screenshot_path' => 'task-screenshots/favicon-icon.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'public/favicon.ico is a 0-byte empty file')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
