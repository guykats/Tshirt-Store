<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', "Dashboard's per-agent status dropdown (IDLE/PENDING_APPROVAL/EXECUTING) is hardcoded, untranslated English with no i18n keys")
            ->update([
                'status' => 'done',
                'commit_sha' => 'c04d9e9bc756cec5e798f9b9e92be1ba6cfc2415',
                'screenshot_path' => 'task-screenshots/agent-status-dropdown-hebrew-i18n.png',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Dashboard's per-agent status dropdown (IDLE/PENDING_APPROVAL/EXECUTING) is hardcoded, untranslated English with no i18n keys")
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'screenshot_path' => null,
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }
};
