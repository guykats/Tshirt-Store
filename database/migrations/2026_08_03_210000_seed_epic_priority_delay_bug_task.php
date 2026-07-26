<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'title' => 'Newly proposed epics always outrank any epic an admin just explicitly delayed',
            'description' => "EpicController::delay() (app/Http/Controllers/Api/EpicController.php:71-80) sends an epic 'to the back of the line' by setting its priority to 1 + the current max priority among proposed epics — the Epics tab sorts proposed epics ascending by priority (EpicController::index(), same file, lines 17-24), so this correctly pushes the delayed epic to the end at the moment of the click. But every epic proposed live through the Visioner Agent chat is inserted with a hard-coded priority of 0 (VisionerChatController::proposeEpic, app/Http/Controllers/Api/VisionerChatController.php:90) — the same value the epics.priority column defaults to (database/migrations/2026_07_17_120000_create_epics_table.php:17). Sequence: admin delays epic A, which gets priority=3 (say) to sit behind existing proposed epics; minutes later the admin proposes a new epic B via chat, which gets priority=0 and now sorts ahead of A on next load, silently undoing the deprioritization the admin just performed. This repeats indefinitely — any newly chatted-in epic always jumps back in front of anything ever delayed, with no way for the delay to 'stick' against future proposals. SyncEpicDecisions::handle() (app/Console/Commands/SyncEpicDecisions.php:77) inherits the same defect when inserting live-only epics into the CI replica ('priority' => \$epic['priority'] ?? 0). Fix direction: proposeEpic() (and SyncEpicDecisions's live-only insert path) should compute a 'front of the current proposed queue' priority the mirror image of how delay() computes 'back of the queue' — e.g. min(priority) - 1 among currently-proposed epics, falling back to 0 only when the proposed queue is empty — so a freshly proposed epic lands at the front of what's still undecided without overtaking anything an admin already explicitly delayed.",
            'agent_name' => 'Dev Agent',
            'status' => 'todo',
            'task_type' => 'bugfix',
            'approved_for_dev' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Newly proposed epics always outrank any epic an admin just explicitly delayed')
            ->delete();
    }
};
