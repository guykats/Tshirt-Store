<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Owner-requested board reset: keep only real shipped history (status =
        // 'done'), clear every todo/in_progress/blocked task and every epic.
        // project_tasks.epic_id is ->nullOnDelete(), so deleting epics first
        // would be safe too, but tasks are cleared first regardless since most
        // of what's being removed here is unapproved todo backlog noise.
        DB::table('project_tasks')->where('status', '!=', 'done')->delete();
        DB::table('epics')->delete();
    }

    public function down(): void
    {
        // Not reversible - the deleted rows' original data isn't recoverable
        // from this migration alone.
    }
};
