<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Remove sidebars sitewide; redesign Team Management as a distinct dark-mode section',
            'description' => 'Direct owner-authorized creative redesign, not picked from the todo backlog: (1) removed AdminSidebar/AdminLayout entirely from all 10 /dashboard/* routes; (2) the 7 store-admin pages (Dashboard, Products, Coupons, Reviews, Design, Style Guide, Audit Log) now use a new light-themed horizontal top nav (StoreAdminNav/StoreAdminLayout) instead of a sidebar; (3) Team Management (Board/Epics/Chat) got its own permanently dark-mode sub-application (TeamManagementNav/TeamManagementLayout) with a 3-tab top bar, rendered outside the storefront Layout entirely so it reads as a genuinely distinct tool rather than a re-skinned page. Sidebar-vs-topbar and dark-dev-tool-palette research (cited in full in the task instructions) grounded the structure: 3-6 primary sections fit a top tab bar better than a sidebar; a near-black surface ladder (bg/surface-1/surface-2), hairline borders, and exactly one muted indigo accent, scoped to a `.team-dark` class (resources/css/app.css) rather than :root so it can never leak into the storefront/store-admin light theme. All color pairings were checked against WCAG AA (4.5:1 body text / 3:1 large text and UI components) before being chosen.',
            'agent_name' => 'Creative Agent',
            'task_type' => 'design',
            'status' => 'in_progress',
            'approved_for_dev' => true,
            'commit_sha' => null,
            'screenshot_path' => null,
            'blocked_reason' => null,
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Remove sidebars sitewide; redesign Team Management as a distinct dark-mode section')
            ->delete();
    }
};
