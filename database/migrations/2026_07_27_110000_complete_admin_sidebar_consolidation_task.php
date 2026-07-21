<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            'epic_id' => null,
            'title' => 'Consolidate store-management tabs into a grouped admin sidebar',
            'description' => 'Quick IA-best-practices check first (5-7 top-level nav items is the standard ceiling before grouping/nesting is warranted) - the flat top nav had grown to 9 separate /dashboard/* links. Replaced with a single "Dashboard" top-nav entry point; every admin page (Dashboard, Team Management Board/Epics/Chat, Products, Coupons, Reviews, Design, Style Guide, Audit Log) now shares one AdminLayout + AdminSidebar with items grouped under Team Management / Store / Site / System headers, active page highlighted. Supersedes the narrower TeamManagementLayout that only covered 3 of these 9 pages.',
            'agent_name' => 'Dev Agent',
            'task_type' => 'feature',
            'status' => 'done',
            'commit_sha' => 'b8cba8ec7992208b087b9030e603c8ab7dd77289',
            'screenshot_path' => 'task-screenshots/admin-sidebar-grouped.png',
            'blocked_reason' => null,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', 'Consolidate store-management tabs into a grouped admin sidebar')->delete();
    }
};
