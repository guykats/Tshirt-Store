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
                'title' => 'Cancelling or refunding an order never restores the reserved stock it decremented',
                'description' => 'app/Http/Controllers/Api/CheckoutController.php decrements product_variants.stock_quantity synchronously when an order is created (before payment is even captured), but neither OrderController::cancel() nor OrderController::refund() (app/Http/Controllers/Api/OrderController.php) ever increments it back — they only flip the status/payment_status columns. Grepping app/ for increment(\'stock_quantity\' only finds the one decrement in checkout, no matching restore anywhere. Effect: every cancelled or refunded order permanently shrinks real sellable inventory and will eventually produce false out-of-stock/low-stock states that don\'t reflect reality. Fix by restoring each order item\'s quantity back onto its product_variant\'s stock_quantity inside cancel() and refund(), inside the existing DB transaction, and clear low_stock_alerted_at if the restored quantity crosses back above the alert threshold (mirror the existing threshold-check logic already used by Admin\\ProductVariantController::update and the low-stock-alert task). Add feature tests asserting stock_quantity is restored on both cancel and refund paths.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job',
                'description' => 'Because CheckoutController::store() decrements stock at order-creation time rather than at payment capture, an order that a shopper starts but never completes (closed tab, failed PayPal capture, etc.) sits indefinitely in a pending/unpaid state while its reserved stock is never released — bootstrap/app.php\'s withSchedule() only registers the daily app:backup-database command, and there is no app/Console/Commands/*Expire*.php or equivalent anywhere in the codebase. Add a new scheduled Artisan command (follow the shape of app/Console/Commands/BackupDatabase.php: Process::fake()-testable, logs a SystemEvent on completion) that finds orders past a reasonable age (e.g. 30-60 minutes) still unpaid/not approved, restores their reserved stock, and marks them expired/cancelled so they stop counting against inventory. Wire it into bootstrap/app.php\'s withSchedule() at a sensible cadence (e.g. everyFifteenMinutes()). This depends on stock actually being restorable on cancellation, so coordinate with (or land after) the "never restores the reserved stock" task above rather than duplicating that logic.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists',
                'description' => 'CouponService and the Coupon model correctly validate and redeem coupon codes at checkout (expiration, usage limits) — that part of the earlier coupon-codes task is genuinely solid and tested. But routes/api.php has no admin/coupons* routes at all (contrast with the full admin/products*, admin/products/{product}/variants*, and admin/products/{product}/images* blocks that already exist for products), there is no CouponController under app/Http/Controllers/Api/Admin, no coupon page anywhere under resources/js/pages, and DatabaseSeeder never seeds a single Coupon row. The only way to get a redeemable coupon into the coupons table today is a raw DB insert, which makes the shipped checkout feature operationally unusable. Add an admin CouponController (index/store/update/destroy, or at minimum store/update/destroy plus a list endpoint) under the existing admin/* route group and auth pattern, and a simple admin React page for managing coupons, following the same structural pattern already established by resources/js/pages/ProductManagement.jsx for product/variant CRUD (table + create/edit form, same design-system components). Cover the new endpoints with feature tests the way admin product CRUD is already tested.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Admin order queues have no way to search or filter by customer name or email',
                'description' => 'OrderController::index() (app/Http/Controllers/Api/OrderController.php) only accepts a status query param for admin requests, and resources/js/pages/Dashboard.jsx only ever fetches a few hardcoded status buckets (pending_approval, fulfillment-eligible, refundable) via fetchAllPages — there is no general "browse/search all orders" view anywhere in the admin UI or API. If an admin needs to find one specific customer\'s order for a support request or refund dispute outside those fixed buckets, there is currently no way to do it without a direct DB query. Add an optional search/email query param to OrderController::index() for admin requests (matching against the order\'s customer name/email, case-insensitively, SQLite-safe per this repo\'s convention of avoiding MySQL-only SQL functions) and a corresponding search input in Dashboard.jsx wired to it. Add a feature test covering the new search param returning the right subset of orders.',
                'agent_name' => 'Dev Agent',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Cancelling or refunding an order never restores the reserved stock it decremented',
            'Stock reserved by an abandoned, never-paid checkout is locked forever with no expiry job',
            'There is no admin way to create, edit, or deactivate a coupon code — only server-side redemption exists',
            'Admin order queues have no way to search or filter by customer name or email',
        ])->delete();
    }
};
