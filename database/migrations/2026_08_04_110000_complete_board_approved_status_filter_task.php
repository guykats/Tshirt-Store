<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Add an Approved status filter to the project board',
            'description' => 'Owner-requested: a task already has both a status enum (todo/in_progress/blocked/done) and a separate approved_for_dev flag - the PM Agent only builds todo tasks with approved_for_dev=1. Rather than collapsing that into a new status enum value (which would touch the PM Agent\'s task-selection query and every other status read-site), added "approved" as a filter-only pseudo-status: ProjectTaskController::index special-cases status=approved to where(status=todo)->where(approved_for_dev=1), and returns an approved count alongside the real per-status counts. ProjectProgress.jsx gets a fifth stat tile/filter button reusing the existing tile grid, with English/Hebrew translations and its own amber tint distinct from the other four. Verified with a new feature test and a Playwright screenshot confirming the tile renders and the filter actually narrows the table.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => 'e500b8fe144617862553b0b676685caa671d404e',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Add an Approved status filter to the project board')->delete();
    }
};
