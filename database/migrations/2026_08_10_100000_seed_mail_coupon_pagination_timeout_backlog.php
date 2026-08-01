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
                'title' => 'OrderController::refund and advanceStatus let an unhandled mail exception surface as a false failure after the order state already changed',
                'description' => "app/Http/Controllers/Api/OrderController.php:185-187 (advanceStatus's shipped/delivered mail) and :293 (refund's OrderRefundedMail) call Mail::to(...)->send(...) with no try/catch, unlike CheckoutController::capture (which wraps its post-capture mail) and PayPalWebhookController::markPaid (which wraps its confirmation mail) elsewhere in this codebase. If the mail transport throws (SMTP timeout, misconfigured mailer), the exception propagates after the DB transaction has already committed the refund or status advance and restored stock — so the admin sees a 500 error and reasonably assumes the action failed, when the order was in fact already refunded or advanced. That can lead to a confused support conversation or a duplicate manual refund attempt on an order that was already refunded. Fix by wrapping these Mail::send calls in try/catch and logging failures instead of letting them bubble up, matching the pattern already used in CheckoutController and PayPalWebhookController.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Percent-type coupons have no upper bound on value, in neither the admin form nor the backend validation',
                'description' => "app/Http/Controllers/Api/Admin/CouponController.php:93 validates 'value' as ['required', 'numeric', 'min:0'] regardless of 'type', and resources/js/pages/CouponManagement.jsx's value input renders with min=\"0\" and no max, unconditioned on type. An admin can save a coupon with type=percent, value=500 and the API accepts it with no validation error. Coupon::discountFor() silently clamps the actual discount to the order subtotal, so the coupon record and admin table both display a nonsensical \"500% off\" even though it only ever discounts 100% at checkout — a plausible fat-finger mistake (500 instead of 50, 100 instead of 10) that produces confusing, incorrect-looking coupon records with no warning. Fix by capping percent-type value at 100 in both the backend validation (conditional max:100 rule when type=percent) and the admin form.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Orders.jsx never paginates past the first 20 orders, silently hiding a customer's older order history",
                'description' => "app/Http/Controllers/Api/OrderController.php's index paginates 20-at-a-time (paginate(20)) and returns meta.last_page/current_page, but resources/js/pages/Orders.jsx fetches /api/orders with no page param, reads only res.data.data, and never reads res.data.meta or renders any pager — unlike Dashboard.jsx's fetchAllPages helper or CouponManagement.jsx/AdminReviews.jsx's prev/next controls. Any customer with more than 20 orders will never see their 21st-and-older orders on the 'My Orders' page: no error, no 'load more,' those orders simply never appear, along with their invoices and tracking links. Fix by adding pagination controls to Orders.jsx, mirroring the existing admin list pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'The shared frontend axios instance has no request timeout, so a hung backend call leaves the UI stuck indefinitely',
                'description' => "resources/js/lib/api.js's axios.create({...}) instance — used by every page including Checkout.jsx's order creation/approval flow and AuthContext.jsx's login/register — sets baseURL, withCredentials, and headers but no timeout. If any backend call hangs (e.g. a slow PayPal API call, or a database lock wait), the browser request never resolves or rejects, so loading state like Checkout.jsx's disabled 'Continue to Payment' button stays stuck forever with no error shown, since nothing ever reaches a .catch()/finally() block and the shopper has no way to retry. Fix by adding a sane default timeout (e.g. 15-30s) to the shared axios instance.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'quality',
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
            'OrderController::refund and advanceStatus let an unhandled mail exception surface as a false failure after the order state already changed',
            'Percent-type coupons have no upper bound on value, in neither the admin form nor the backend validation',
            "Orders.jsx never paginates past the first 20 orders, silently hiding a customer's older order history",
            'The shared frontend axios instance has no request timeout, so a hung backend call leaves the UI stuck indefinitely',
        ])->delete();
    }
};
