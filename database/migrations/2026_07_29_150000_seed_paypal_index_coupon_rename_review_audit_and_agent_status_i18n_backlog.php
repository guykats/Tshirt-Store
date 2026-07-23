<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => null,
                'title' => "orders.paypal_order_id has no database index despite being the lookup key for every PayPal webhook",
                'description' => "database/migrations/2026_07_16_100500_create_orders_table.php:23 declares paypal_order_id as a plain \$table->string(...)->nullable() with no ->index(). PayPalWebhookController::markPaid() and markFailed() (app/Http/Controllers/Api/PayPalWebhookController.php:60, :93) both do Order::where('paypal_order_id', \$paypalOrderId) on every single PAYMENT.CAPTURE.COMPLETED/DENIED webhook PayPal delivers, and CheckoutController::capture() looks up the same column on every capture attempt. orders.status and orders.user_id already got dedicated indexes for this exact reason (see 2026_07_27_100000-era migrations), but paypal_order_id — arguably the hottest lookup column of all, hit by external webhook traffic outside the app's own control — was missed. As the orders table grows past a trivial row count this becomes a full table scan on every webhook delivery and every capture call. Fix: a migration adding \$table->index('paypal_order_id') (and ideally paypal_transaction_id, used by markPaid()'s duplicate-capture guard) to the orders table.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Renaming a coupon's code silently resets its per-customer redemption cap for every past order",
                'description' => "Admin\\CouponController::update() (app/Http/Controllers/Api/Admin/CouponController.php:60-73) lets an admin freely edit a coupon's code field via its validated() rule (Rule::unique('coupons','code')->ignore(\$coupon->id) — no restriction once created). But CouponService::customerLimitReached() (app/Services/CouponService.php:73-84), which enforces max_redemptions_per_user, matches on \$buyer->orders()->where('discount_code', \$coupon->code) — 'discount_code' is a snapshot of whatever code string was typed at checkout time, frozen on the order row, while \$coupon->code is the coupon's *current* value. If an admin renames 'SAVE10' to 'SAVE10B' (e.g. to relaunch it, or fix a typo), every prior order's discount_code still reads 'SAVE10', so the count of orders matching the new code drops to zero and every customer who already hit their per-customer cap under the old code can immediately redeem it again under the new one. Fix: either freeze coupon codes as immutable after creation, or have customerLimitReached() key off the coupon's stable id (a coupon_id column on orders, or joining through a redemption log) rather than the mutable code string.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Creating or editing a review never writes a SystemEvent, unlike every other mutation in the app including review deletion",
                'description' => "ReviewController::store() (app/Http/Controllers/Api/ReviewController.php:59-89) and update() (:100-113) both persist the review with no corresponding SystemEvent::log() call, while destroy() (:144-160) right below them does log 'review.deleted' with the reviewer's name and product. Every other mutating admin-visible action in this app (coupon create/update, order refund/cancel/advance, product update, backup completion) is audited the same consistent way, making AuditLog.jsx a genuine record of what happened to the store — except a review's actual content changing (a customer editing their rating from 5★ down to 1★, or up) or a brand-new review appearing leaves zero trace, even though that's exactly the kind of reputational/moderation-relevant event an admin reviewing the audit log would want visibility into. Fix: add SystemEvent::log('review.created', ...) in store() and SystemEvent::log('review.updated', ...) in update(), mirroring destroy()'s existing call shape, and add both new event types to AuditLog.jsx's event-type filter alongside the fix already tracked for its other missing types.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Dashboard's per-agent status dropdown (IDLE/PENDING_APPROVAL/EXECUTING) is hardcoded, untranslated English with no i18n keys",
                'description' => "AgentRow (resources/js/pages/Dashboard.jsx:745-773), rendered for every row of the live 'Agent Status' table (t('dashboard_agents'), used at :360-374), hardcodes its <select> options as raw JSX literals — <option value=\"idle\">IDLE</option>, <option value=\"pending_approval\">PENDING_APPROVAL</option>, <option value=\"executing\">EXECUTING</option> at lines 757-759 — with no t(...) call, unlike literally every other label on this admin page (dashboard_agent_name, progress_status_in_progress, save, etc., all resolved through resources/js/i18n/index.js). A Hebrew-locale admin viewing/toggling this dropdown sees three untranslated, LTR-only English constants sitting inside an otherwise fully bilingual, RTL-aware page — breaking the 'bilingual by default' requirement CLAUDE.md calls out explicitly. Fix: add agent_status_idle/agent_status_pending_approval/agent_status_executing (or similar) keys with real Hebrew translations to i18n/index.js and swap the three <option> literals for t(...) calls.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->where('title', "orders.paypal_order_id has no database index despite being the lookup key for every PayPal webhook")->delete();
        DB::table('project_tasks')->where('title', "Renaming a coupon's code silently resets its per-customer redemption cap for every past order")->delete();
        DB::table('project_tasks')->where('title', "Creating or editing a review never writes a SystemEvent, unlike every other mutation in the app including review deletion")->delete();
        DB::table('project_tasks')->where('title', "Dashboard's per-agent status dropdown (IDLE/PENDING_APPROVAL/EXECUTING) is hardcoded, untranslated English with no i18n keys")->delete();
    }
};
