<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Remove the duplicate Project Progress widget from the Store Admin Dashboard',
            'description' => 'Owner reported Dashboard.jsx (Store Admin) had its own inline "Project Progress" section - a status-count grid (taskCounts state, ProgressStat component, loadTaskCounts fetching /api/project-tasks) plus a "View full board" link - that duplicated the actual Team Management board, reachable one click away via the existing "Team Management" nav pill in StoreAdminNav.jsx. Removed the widget, its state/fetch, the now-unused ProgressStat component, and the two orphaned dashboard_progress/dashboard_progress_view_all i18n keys (English + Hebrew). Verified with the full vitest suite (69 tests), php artisan test (408 tests), npm run build, and a Playwright screenshot confirming the section no longer renders on /dashboard.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'cleanup',
            'requested_by' => 'Guy',
            'status' => 'done',
            'approved_for_dev' => true,
            'commit_sha' => 'dea98e1c2f8d35a9b4bd9ec1875a663175abb05c',
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Remove the duplicate Project Progress widget from the Store Admin Dashboard')->delete();
    }
};
