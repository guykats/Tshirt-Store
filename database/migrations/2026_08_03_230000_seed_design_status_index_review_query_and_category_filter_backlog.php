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
                'title' => 'Checkout stock/availability errors are never translated for Hebrew shoppers',
                'description' => 'CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php:132, 136, 210) returns raw English strings — \'This product is not currently available.\' and \'Not enough stock for the requested quantity.\' — with no __() wrapper. Checkout.jsx\'s translateCheckoutError() (resources/js/pages/Checkout.jsx:14-35) only maps one specific coupon-rejection message (KNOWN_BACKEND_ERROR_KEYS) and falls back to rendering the raw backend message verbatim for everything else, so a Hebrew-locale shopper who selects a just-deactivated product or an over-quantity out-of-stock variant sees untranslated English inline in an otherwise fully-Hebrew checkout form — distinct from the already-tracked gap where only 1 of CouponService\'s 4 rejection messages is translated. Wrap both CheckoutController messages in __() with translation keys, add real English/Hebrew entries in resources/js/i18n/index.js, and extend translateCheckoutError()\'s known-key map (or switch to a shared key-based contract between backend and frontend) to cover them. Feature/component test: an out-of-stock add-to-checkout attempt with the Hebrew locale active renders the Hebrew string, not the raw English one.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'DesignController::approve()/reject() have no status guard, unlike the equivalent fix already applied to EpicController',
                'description' => 'app/Http/Controllers/Api/DesignController.php:26-72 — approve() and reject() call $design->update([...]) unconditionally regardless of the design\'s current status (pending_approval/approved/rejected/archived), with no check that it\'s still pending_approval first. An admin can re-approve an already-rejected design, or re-reject a design that\'s already approved and built into a live Product (see the separately-tracked "Rejecting a Design after it\'s already been built into a live Product" gap, which assumes reject() only ever happens once), or a stale double-click/double-tab can silently flip status twice and overwrite approved_by/approved_at. This is the exact class of bug already fixed for EpicController::approve()/reject()/delay() — apply the same guard here: only allow the transition when status is currently pending_approval, returning a 422/409 otherwise. Feature tests: approving an already-approved or already-rejected design is rejected with a clear error and leaves its status/approved_by/rejection_reason unchanged.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'orders.discount_code has no database index despite being scanned on every coupon-capped checkout',
                'description' => 'database/migrations/2026_07_21_101500_add_discount_columns_to_orders_table.php adds discount_code as a plain nullable string column with no index. CouponService::customerLimitReached() (app/Services/CouponService.php:85) runs $buyer->orders()->where(\'discount_code\', $coupon->code)->whereNotIn(\'status\', ...)->count() on every checkout attempt that uses a coupon with a per-customer cap — an unindexed string-equality table scan across the buyer\'s full order history that gets slower as the orders table grows, the same class of gap already fixed for orders.user_id/paypal_order_id/payment_status. Add a migration indexing discount_code (a plain index is enough; it doesn\'t need to be unique since many orders can share a code). Verify with EXPLAIN or a query-count assertion in a feature test that the customerLimitReached() lookup uses the new index.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'ReviewController::index() runs 2 avoidable extra aggregate queries per product-detail page view',
                'description' => 'app/Http/Controllers/Api/ReviewController.php:20-33 fetches a product\'s review list, then separately runs $product->reviews()->avg(\'rating\') and $product->reviews()->count() as two more standalone queries to build the \'meta\' block — three queries total for one endpoint. ProductController.php:25-26 and 58-59 already demonstrate the correct one-query pattern for the identical aggregate (withAvg(\'reviews\',\'rating\')->withCount(\'reviews\') on the list query, loadAvg()/loadCount() on a single model) but ReviewController never adopted it. Switch ReviewController::index() to $product->loadAvg(\'reviews\',\'rating\')->loadCount(\'reviews\') (or compute both from the already-fetched $reviews collection) to drop from 3 queries to 1. Add a query-count assertion (e.g. DB::enableQueryLog() or assertQueryCount if available) to a feature test covering this endpoint so the regression can\'t silently come back.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Design category is captured and shown to admins but never exposed as a public catalog filter',
                'description' => 'database/migrations/2026_07_16_100200_create_designs_table.php:15 adds designs.category and DesignResource.php:16 exposes it to the admin Design review queue, but ProductController (app/Http/Controllers/Api/ProductController.php) never selects, filters, or exposes it on ProductResource, and Catalog.jsx (resources/js/pages/Catalog.jsx) only offers free-text search plus a sort dropdown — no category/collection browsing exists anywhere on the public catalog despite the underlying data (e.g. seeded categories like \'cultural-signal\' per DatabaseSeeder) already existing on every product\'s approved design. Add the design\'s category to ProductResource (via the product\'s active/approved Design relation), a `category` query filter on ProductController::index(), and a category filter control on Catalog.jsx with real English/Hebrew labels for each known category value. Feature test: filtering the catalog by a category returns only products whose design carries it.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
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
            'Checkout stock/availability errors are never translated for Hebrew shoppers',
            'DesignController::approve()/reject() have no status guard, unlike the equivalent fix already applied to EpicController',
            'orders.discount_code has no database index despite being scanned on every coupon-capped checkout',
            'ReviewController::index() runs 2 avoidable extra aggregate queries per product-detail page view',
            'Design category is captured and shown to admins but never exposed as a public catalog filter',
        ])->delete();
    }
};
