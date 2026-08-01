<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $backlog = [
            [
                'title' => "Not-yet-approved or rejected designs can still appear on the live storefront",
                'description' => "Admin\\ProductController::validated (app/Http/Controllers/Api/Admin/ProductController.php:122-131) validates design_id with only ['required', 'integer', 'exists:designs,id'] -- it never checks Design::status, so an admin can attach a design that is still 'pending_approval' or was explicitly 'rejected' to a product. On the public side, ProductController::index and ::show (app/Http/Controllers/Api/ProductController.php:13-67) only filter on Product::status === 'active'; neither ever joins or filters on the attached design's status, so that product (and the not-yet-vetted artwork on it) is served to every storefront visitor once the product itself is active. The gap runs both directions: DesignController::reject (app/Http/Controllers/Api/DesignController.php:48-67) flips a design to 'rejected' with no check for whether it is already attached to a live product, so rejecting a design after the fact does nothing to the product already displaying it. Failure scenario: admin attaches a design before Discover-tab review finishes (or approves a product, then later rejects the design after spotting a problem) -- the storefront keeps showing it either way, silently bypassing the design-approval workflow the Discover tab exists to enforce. Fix by validating design_id against status='approved' in Admin\\ProductController::validated, and/or filtering products through design.status in the public queries.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Every customer login redirects to the admin-only /dashboard and silently bounces back to /login",
                'description' => "Login.jsx:26 unconditionally calls navigate('/dashboard') after a successful login, regardless of the authenticated user's role. But /dashboard is wrapped in RequireAdmin (resources/js/App.jsx:48-55, mounted at App.jsx:159-168), which renders <Navigate to=\"/login\" replace /> for any user whose role !== 'admin' (App.jsx:52). Register.jsx:28 correctly navigates to '/' instead, confirming this is an oversight in Login.jsx rather than an intentional admin-first flow. Failure scenario: any ordinary customer (the overwhelming majority of accounts) logs in through the normal /login form, briefly flashes to /dashboard, and is immediately kicked back to /login with no error message or explanation -- they never learn login actually succeeded, and Login.test.jsx never exercises the real RequireAdmin route so this regresses silently. Fix by branching on the returned user's role (or simply navigating to '/' for non-admins, matching Register.jsx).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Discover tab's suggestion feed silently loses earlier same-day batches once more than 20 exist",
                'description' => "Discover.jsx:76-93's loadLatestBatch() calls api.get('/api/design-suggestions') with no page parameter and reads res.data.data directly, ignoring the pagination meta the backend returns -- DesignSuggestionController::index paginates results 20 per page. Suggestions accumulate per batch_date rather than being replaced (there is no endpoint that clears a prior batch before generating a new one), so if 'Generate now' is used more than once on the same day (e.g. two clicks, or a manual generate on top of the nightly 3am job), the combined pending suggestions for that batch_date exceed 20. Only the first page's worth is ever fetched and rendered; the rest exist in the database but become permanently invisible and unreviewable in the admin UI (no next-page control exists on this page), so they neither get kept, discarded, nor cleaned up through the intended workflow. Fix by either paginating the Discover UI like Orders.jsx does, or constraining suggestion generation server-side to at most one batch per batch_date.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'epic_id' => null,
                'commit_sha' => null,
                'screenshot_path' => null,
                'blocked_reason' => null,
                'completed_at' => null,
            ], $task, [
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            "Not-yet-approved or rejected designs can still appear on the live storefront",
            "Every customer login redirects to the admin-only /dashboard and silently bounces back to /login",
            "Discover tab's suggestion feed silently loses earlier same-day batches once more than 20 exist",
        ])->delete();
    }
};
