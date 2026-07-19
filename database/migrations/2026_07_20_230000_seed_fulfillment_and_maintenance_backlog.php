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
                'title' => 'Order fulfillment status progression + shipment emails',
                'description' => 'The orders.status enum already includes processing, shipped, and delivered (see the orders table migration and Order model), but OrderController only exposes approve/cancel — there is no endpoint or admin UI action that ever moves an order into processing/shipped/delivered, and app/Mail/ only has OrderConfirmationMail (sent on PayPal capture). Add an admin-only status-advance endpoint (e.g. POST /api/orders/{order}/advance-status or a PATCH accepting the next valid status) that only allows forward transitions in the existing enum order, logs a SystemEvent like the approve/cancel actions do, and sends a new bilingual OrderShippedMail / OrderDeliveredMail (following OrderConfirmationMail\'s pattern) on the shipped and delivered transitions respectively. Add the corresponding admin dashboard control and update the customer-facing Orders.jsx status badges/copy if needed. Write feature tests: admin can advance pending_approval/approved orders through the valid sequence, cannot skip/reverse statuses, non-admin is forbidden, and the right mail is queued at each transition (Mail::fake()).',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Admin product & variant CRUD',
                'description' => 'There is no way to create, edit, or delete products/variants anywhere in this app — routes/api.php has no product store/update/destroy routes, and the dashboard has no product management UI at all; every product currently in the catalog only exists via seeders. Add admin-only endpoints (POST/PUT/DELETE under /api/admin/products, following the existing admin-gated pattern used by TestimonialController/SiteSettingController) for managing Product and ProductVariant rows (name, description, price, images, per-variant size/color/stock_quantity), plus a dashboard UI section to list/create/edit/delete products, reusing the existing #[Fillable] attribute pattern and portable SQL conventions. Write feature tests: admin can create/update/delete a product and its variants, non-admin/guest is forbidden, validation rejects missing required fields, and deleting a product with existing orders is handled sensibly (e.g. soft-delete or block deletion rather than breaking historical order references).',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'FAQ page',
                'description' => 'There is no FAQ page anywhere in resources/js/pages/ despite the site handling real payment, sizing, and shipping questions that would otherwise land as support email — About.jsx and the new Privacy.jsx/Terms.jsx establish a clear static-content-page pattern (useDocumentMeta for title, DesignArt motif, parchment/ink/brass styling) that a FAQ page should follow. Add a bilingual /faq page covering realistic customer questions for a small apparel store (sizing/fit, shipping times, returns/cancellation — link the real self-service cancellation window from Order::isCancellable(), payment methods via PayPal, order tracking, contact), with real (not lorem ipsum) English and natural Hebrew copy, and add a footer link from Layout.jsx alongside the Privacy/Terms links.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'Merge pending Dependabot dependency PRs',
                'description' => 'GitHub currently has 5 open Dependabot PRs against main, all with fully green CI (PHP 8.3/8.4/8.5 matrix): #1 appleboy/ssh-action 1.2.0→1.2.5, #2 actions/setup-node 4→7, #3 actions/checkout 6→7, #4 appleboy/scp-action 0.1.7→1.0.0, and #5 concurrently 9.2.4→10.0.3 (npm devDependency only, not a runtime dependency). None have been reviewed or merged. For each: read the release notes for any breaking changes relevant to how this repo uses the action/package (deploy.yml uses appleboy/ssh-action and appleboy/scp-action for the Hostinger deploy, tests.yml/deploy.yml use actions/checkout and actions/setup-node, package.json scripts use concurrently for `npm run dev`), merge the ones that are safe minor/patch bumps with passing CI, and verify the next scheduled CI run and this repo\'s own deploy still succeed afterward. `composer audit` and `npm audit` both currently report zero vulnerabilities, so this is routine hygiene, not urgent CVE remediation — do not force through anything whose CI is red or whose changelog signals an actual breaking change without adapting the calling workflow first.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Order fulfillment status progression + shipment emails',
            'Admin product & variant CRUD',
            'FAQ page',
            'Merge pending Dependabot dependency PRs',
        ])->delete();
    }
};
