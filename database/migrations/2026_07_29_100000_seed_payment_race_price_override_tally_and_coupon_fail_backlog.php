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
                'title' => "A webhook race between PayPal's capture webhook and the buyer's own capture call can send duplicate order-confirmation emails",
                'description' => "PayPalWebhookController::markPaid() (app/Http/Controllers/Api/PayPalWebhookController.php:50-82) and CheckoutController::capture() (app/Http/Controllers/Api/CheckoutController.php:243-295) both mark an order paid with the same unlocked read-status-then-write pattern: check payment_status !== 'paid', then update() and send OrderConfirmationMail. Neither wraps this in DB::transaction()/lockForUpdate(), unlike OrderController::cancel()/refund() and ExpireAbandonedOrders, which deliberately row-lock the order first to avoid double-processing. Failure scenario: PayPal typically fires PAYMENT.CAPTURE.COMPLETED within milliseconds of a capture succeeding. If the buyer's browser is still inside CheckoutController::capture() (past its own payment_status==='paid' early-out, still awaiting captureOrder()'s network round-trip) when the webhook lands, markPaid() reads the still-unpaid order, marks it paid, and emails OrderConfirmationMail. Moments later the original capture() call finishes, unconditionally re-marks it paid, and sends a second OrderConfirmationMail — the buyer gets two confirmation emails and system_events gets two separate order.paid rows for one purchase. Fix: wrap both paths' update+email in DB::transaction() with Order::where('id', \$id)->lockForUpdate()->first(), re-checking payment_status !== 'paid' under the lock before writing or emailing, matching OrderController::cancel()'s existing lock-then-recheck pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'A variant price_override actually changes what a shopper is charged but is never shown to them anywhere before checkout',
                'description' => "CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php:139) bills \$unitPrice = (float) (\$variant->price_override ?? \$variant->product->base_price) — price_override, when an admin sets one via ProductManagement.jsx (resources/js/pages/ProductManagement.jsx:28,203,225,603-611), is what the buyer is actually charged. But ProductDetail.jsx:156, Checkout.jsx:198, and Catalog.jsx:287 all unconditionally render formatPrice(product.base_price, ...) — none of the three ever reads the selected variant's price_override (already serialized to the frontend via ProductVariantResource.php:18), even after a specific variant is selected. Failure scenario: an admin sets an XXL variant's price_override $2 above the product's base_price (a supported, intended use of the field). A shopper selects that variant on ProductDetail.jsx, sees only the base price through the entire ProductDetail -> Checkout flow, then is billed the higher price_override amount with no on-screen indication anywhere that the charged price differs from what was displayed — only PayPal's own button UI would eventually reveal the real total. This is a real price-transparency/billing-dispute risk, not a cosmetic gap. Fix: have ProductDetail.jsx, Checkout.jsx, and Catalog.jsx resolve and display the selected variant's price_override ?? product.base_price, mirroring the fallback CheckoutController::store() already applies server-side.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Project Progress board's status-count tiles ignore the active status/agent/epic filters, always showing global totals",
                'description' => "ProjectTaskController::index() (app/Http/Controllers/Api/ProjectTaskController.php:17-39) builds \$tasks with status/agent/epic_id when() filters applied correctly, but \$tally — the query backing the 'counts' response key — is a brand-new ProjectTask::query()->selectRaw('status, count(*) as total')->groupBy('status') with none of those filters applied, so it always counts every project_task row in the table regardless of what was requested. ProjectProgress.jsx:46-59 renders its four stat tiles (todo/in_progress/blocked/done) directly from this unfiltered counts object. Failure scenario: an admin on /dashboard/progress picks a specific agent from the agent-filter dropdown to see that agent's queue — the task table below correctly narrows to that agent's rows, but the tiles above keep showing counts for every agent combined (e.g. '12 todo' when the selected agent actually has 2 in the filtered list right below it), misleading the admin about how much work is actually queued for the agent or status they're looking at. Fix: apply the same status/agent/epic_id when() filters used for \$tasks to the \$tally query as well, so the tiles reflect the filtered set being viewed.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "A declined PayPal capture still counts against a coupon's per-customer redemption cap for up to an hour",
                'description' => "CouponService::customerLimitReached() (app/Services/CouponService.php:78-90) counts a buyer's redemptions as \$buyer->orders()->where('discount_code', \$coupon->code)->whereNotIn('status', self::RELEASED_ORDER_STATUSES)->count(), where RELEASED_ORDER_STATUSES is only ['cancelled', 'refunded'] (CouponService.php:18). But when a PayPal capture is declined, CheckoutController::capture() (app/Http/Controllers/Api/CheckoutController.php:266-270) only sets payment_status = 'failed' — the order's status stays 'pending_approval', which is not in RELEASED_ORDER_STATUSES. The only thing that eventually cancels (and thus releases) that order is ExpireAbandonedOrders (app/Console/Commands/ExpireAbandonedOrders.php:24-37), gated on the checkout.reservation_minutes window (60 minutes by default). Failure scenario: a coupon has max_redemptions_per_user = 1. A buyer applies it, checkout creates a pending_approval order and increments redemptions_count, then their PayPal payment is declined. If they immediately retry checkout with a fresh cart and the same code, customerLimitReached() counts the still-pending_approval failed order against their cap and rejects the coupon with 'You have already used this coupon the maximum number of times allowed' — even though they were never actually charged — until ExpireAbandonedOrders cancels the stale order up to an hour later. For a coupon with a limited global max_redemptions, the failed order also keeps redemptions_count inflated the whole time, potentially blocking other customers too. Fix: either have capture()'s failure branch immediately release the order's stock/coupon reservation instead of waiting for the reservation window, or have customerLimitReached() (and the equivalent global-exhaustion check) also exclude orders whose payment_status is 'failed'.",
                'agent_name' => 'Dev Agent',
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
        DB::table('project_tasks')->where('title', "A webhook race between PayPal's capture webhook and the buyer's own capture call can send duplicate order-confirmation emails")->delete();
        DB::table('project_tasks')->where('title', 'A variant price_override actually changes what a shopper is charged but is never shown to them anywhere before checkout')->delete();
        DB::table('project_tasks')->where('title', "Project Progress board's status-count tiles ignore the active status/agent/epic filters, always showing global totals")->delete();
        DB::table('project_tasks')->where('title', "A declined PayPal capture still counts against a coupon's per-customer redemption cap for up to an hour")->delete();
    }
};
