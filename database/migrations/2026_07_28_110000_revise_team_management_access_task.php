<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Split Team Management (Board, Epics, Chat) off the store admin onto its own subdomain')
            ->update([
                'title' => 'Add a persistent floating admin link to Team Management, visible site-wide',
                'description' => 'Revised per the owner: no subdomain split after all. Team Management (Board/Epics/Chat) stays exactly where it is today - same app, same domain, same routes (/dashboard/progress, /dashboard/epics, /dashboard/chat) - it just needs to stop being reachable only through the store-admin sidebar. Instead, add a small floating link/button fixed to a corner of the viewport (e.g. bottom-right, position: fixed) that is visible on every page of the site - including the public storefront (Catalog, product pages, checkout, etc.), not just inside /dashboard - whenever the logged-in user has the admin role. It should mount once at the root layout level (Layout.jsx, alongside the existing admin-only nav link gated on user?.role === \'admin\') so it persists across all routes without being duplicated per-page, and link straight to /dashboard/progress. Style it consistent with the site\'s ink/parchment/brass palette, give it a real accessible label (aria-label, since it may be icon-led), and check it does not visually collide with any other fixed-position UI already on the page (e.g. WishlistButton) before picking a corner. This removes the entire DNS/SSL/separate-subdomain blocker class from the earlier version of this task - it is now a same-app frontend change only, no infra involved.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Add a persistent floating admin link to Team Management, visible site-wide')
            ->update([
                'title' => 'Split Team Management (Board, Epics, Chat) off the store admin onto its own subdomain',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
                'updated_at' => now(),
            ]);
    }
};
