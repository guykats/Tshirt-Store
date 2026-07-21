<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $title = 'Restructure the remaining store-admin sidebar so Store, Settings, and System read as visually distinct zones';

    private string $staleDescription = 'Once Team Management moves to its own subdomain (see the companion task), the store admin sidebar is left with Products/Coupons/Reviews (day-to-day store operations), Design Settings/Style Guide (occasional site configuration), and Audit Log (technical/system). Right now all of these sit in one flat list with identical visual weight, which is exactly the "everything looks the same, all in your face" problem the owner flagged - grouping them under text headers alone (the current AdminSidebar.jsx groups) does not create real visual hierarchy. Research grounding this recommendation: (1) sidebar UX convention treats items near the top of a nav as core-to-the-product and items near the bottom as administrative/secondary - core workflow items belong at the top with full visual weight, config/admin items should be visually and positionally set apart, not peer-weighted (see SaaS sidebar navigation UX pattern writeups, e.g. https://uxplanet.org/best-ux-practices-for-designing-a-sidebar-9174ee0ecaa2 and https://www.saasui.design/blog/saas-navigation-ux-patterns); (2) Shopify\'s own admin treats Settings as a structurally distinct section/entry point from its core operational nav (Orders/Products/Customers), not just another item inside the same flat list (https://help.shopify.com/en/manual/shopify-admin/shopify-admin-overview); (3) audit/system logs are conventionally placed in a clearly separated, deprioritized technical sub-section rather than as a top-level peer to operational pages, since they serve a different audience and cadence (pattern seen in UI Bakery and Zuora\'s audit-log placement docs). Concrete recommendation to implement: keep Dashboard/Products/Coupons/Reviews pinned at the top of AdminSidebar.jsx with their current full visual weight (the pages people touch daily); give Design Settings + Style Guide a real visual break as a "Settings" zone - an actual divider, muted/smaller heading treatment, moved lower in the sidebar - instead of just another text-labeled group; give Audit Log its own most-deprioritized "System" treatment at the very bottom (smaller/muted text, distinct icon, or collapsed behind a disclosure by default) reflecting that it is an infrequent, technical-audience page. The goal is that scanning the sidebar top-to-bottom reads as "what I do daily" -> "what I configure occasionally" -> "technical/system", not one uniform list. Take a Playwright before/after screenshot as evidence per the usual verification bar.';

    private string $fixedDescription = 'Stale-premise correction: this task originally assumed Team Management (Board/Epics/Chat) would be removed from AdminSidebar.jsx entirely once it moved to its own subdomain. The owner later revised that companion task ("Add a persistent floating admin link to Team Management, visible site-wide") - Team Management is staying in-app, on the same routes, inside the store admin sidebar exactly as it is today; it is only gaining an additional site-wide floating link, not losing its sidebar entry. So AdminSidebar.jsx keeps all four of its current groups (Dashboard, Team Management, Store, Site/Settings, System), and this task\'s scope is narrower than originally written: create real visual hierarchy among the three groups that are actually "store admin" rather than dev/project tooling - Products/Coupons/Reviews (day-to-day store operations), Design Settings/Style Guide (occasional site configuration), and Audit Log (technical/system) - leaving the Dashboard link and the Team Management group untouched. Right now all three still sit as flat text-header groups with identical visual weight, which is the "everything looks the same, all in your face" problem the owner flagged - grouping under text headers alone does not create real visual hierarchy. Research grounding this recommendation: (1) sidebar UX convention treats items near the top of a nav as core-to-the-product and items near the bottom as administrative/secondary - core workflow items belong at the top with full visual weight, config/admin items should be visually and positionally set apart, not peer-weighted (see SaaS sidebar navigation UX pattern writeups, e.g. https://uxplanet.org/best-ux-practices-for-designing-a-sidebar-9174ee0ecaa2 and https://www.saasui.design/blog/saas-navigation-ux-patterns); (2) Shopify\'s own admin treats Settings as a structurally distinct section/entry point from its core operational nav (Orders/Products/Customers), not just another item inside the same flat list (https://help.shopify.com/en/manual/shopify-admin/shopify-admin-overview); (3) audit/system logs are conventionally placed in a clearly separated, deprioritized technical sub-section rather than as a top-level peer to operational pages, since they serve a different audience and cadence (pattern seen in UI Bakery and Zuora\'s audit-log placement docs). Concrete recommendation to implement: keep Products/Coupons/Reviews with their current full visual weight (the pages people touch daily), right below Dashboard and Team Management; give Design Settings + Style Guide a real visual break as a "Settings" zone - an actual divider, muted/smaller heading treatment, moved lower in the sidebar - instead of just another text-labeled group; give Audit Log its own most-deprioritized "System" treatment at the very bottom (smaller/muted text, distinct icon, or collapsed behind a disclosure by default) reflecting that it is an infrequent, technical-audience page. The goal is that scanning the sidebar top-to-bottom reads as "project tooling" -> "what I do daily" -> "what I configure occasionally" -> "technical/system", not one uniform list. Take a Playwright before/after screenshot as evidence per the usual verification bar.';

    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', $this->title)
            ->where('description', $this->staleDescription)
            ->update([
                'description' => $this->fixedDescription,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', $this->title)
            ->where('description', $this->fixedDescription)
            ->update([
                'description' => $this->staleDescription,
                'updated_at' => now(),
            ]);
    }
};
