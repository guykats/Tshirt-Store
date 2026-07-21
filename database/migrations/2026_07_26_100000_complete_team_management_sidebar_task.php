<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Team Management sidebar (Board / Epics / Chat)',
            'description' => 'Renamed the top-nav "Progress" tab to "Team Management" (same /dashboard/progress destination). Epics moved out of being a section embedded in that page into its own route (/dashboard/epics, new Epics.jsx). All three pages - Board, Epics, Chat - now share a persistent left sidebar (TeamManagementLayout + TeamManagementSidebar) with the current page highlighted, instead of only being reachable via flat top-nav links.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'commit_sha' => '39940586d8509b02fa796d39b21cfd1c38177700',
            'screenshot_path' => 'task-screenshots/team-management-sidebar.png',
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Team Management sidebar (Board / Epics / Chat)')->delete();
    }
};
