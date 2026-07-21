<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')->insert([
            [
                'epic_id' => null,
                'title' => 'Split Team Management (Board, Epics, Chat) off the store admin onto its own subdomain',
                'description' => 'The owner\'s call: Team Management (/dashboard/progress, /dashboard/epics, /dashboard/chat - i.e. the project_tasks board, epics approvals, and Visioner chat) is internal tooling for running this AI-agent development process, not part of running the store. It should stop living inside the store-admin sidebar entirely and move to its own dashboard on a separate subdomain (suggested: team.guykats.com - adjust the name if a better one is obvious). Suggested approach to keep this cheap: one shared Laravel backend/deploy, not a second app - use Route::domain(\'team.guykats.com\') (or an equivalent host-based route group) to serve a distinct SPA entry point whose routes are exactly today\'s Board/Epics/Chat pages, reusing the existing ProjectProgress.jsx/Epics.jsx/VisionerChat.jsx components rather than forking them. Auth should carry over via the existing Sanctum session cookie - set SESSION_DOMAIN to a shared parent domain (e.g. .guykats.com) in config/session.php and add the new subdomain to SANCTUM_STATEFUL_DOMAINS so one login works on both store.guykats.com and the new subdomain. Once live, remove the "Team Management" group and its three links entirely from AdminSidebar.jsx/App.jsx in the store admin - they move, they do not stay duplicated in both places. Known likely blocker: provisioning the subdomain itself (DNS A/CNAME record + SSL certificate) happens in Hostinger\'s control panel, which is outside anything deploy.yml or an agent\'s GitHub-scoped token can reach - whoever picks this up should build everything that can be built (routing, session config, sidebar removal) and mark it blocked with a clear blocked_reason for the DNS/SSL step specifically, the same category of "needs a human with control-panel access" blocker already documented on tasks 65/94, just at the DNS layer instead of the GitHub Actions layer.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'epic_id' => null,
                'title' => 'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones',
                'description' => 'Once Team Management moves to its own subdomain (see the companion task), the store admin sidebar is left with Products/Coupons/Reviews (day-to-day store operations), Design Settings/Style Guide (occasional site configuration), and Audit Log (technical/system). Right now all of these sit in one flat list with identical visual weight, which is exactly the "everything looks the same, all in your face" problem the owner flagged - grouping them under text headers alone (the current AdminSidebar.jsx groups) does not create real visual hierarchy. Research grounding this recommendation: (1) sidebar UX convention treats items near the top of a nav as core-to-the-product and items near the bottom as administrative/secondary - core workflow items belong at the top with full visual weight, config/admin items should be visually and positionally set apart, not peer-weighted (see SaaS sidebar navigation UX pattern writeups, e.g. https://uxplanet.org/best-ux-practices-for-designing-a-sidebar-9174ee0ecaa2 and https://www.saasui.design/blog/saas-navigation-ux-patterns); (2) Shopify\'s own admin treats Settings as a structurally distinct section/entry point from its core operational nav (Orders/Products/Customers), not just another item inside the same flat list (https://help.shopify.com/en/manual/shopify-admin/shopify-admin-overview); (3) audit/system logs are conventionally placed in a clearly separated, deprioritized technical sub-section rather than as a top-level peer to operational pages, since they serve a different audience and cadence (pattern seen in UI Bakery and Zuora\'s audit-log placement docs). Concrete recommendation to implement: keep Dashboard/Products/Coupons/Reviews pinned at the top of AdminSidebar.jsx with their current full visual weight (the pages people touch daily); give Design Settings + Style Guide a real visual break as a "Settings" zone - an actual divider, muted/smaller heading treatment, moved lower in the sidebar - instead of just another text-labeled group; give Audit Log its own most-deprioritized "System" treatment at the very bottom (smaller/muted text, distinct icon, or collapsed behind a disclosure by default) reflecting that it is an infrequent, technical-audience page. The goal is that scanning the sidebar top-to-bottom reads as "what I do daily" -> "what I configure occasionally" -> "technical/system", not one uniform list. Take a Playwright before/after screenshot as evidence per the usual verification bar.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'design',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Split Team Management (Board, Epics, Chat) off the store admin onto its own subdomain',
            'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones',
        ])->delete();
    }
};
