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
                'status' => 'in_progress',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', "Dashboard's per-agent status dropdown (IDLE/PENDING_APPROVAL/EXECUTING) is hardcoded, untranslated English with no i18n keys")
            ->update([
                'status' => 'todo',
                'updated_at' => now(),
            ]);
    }
};
