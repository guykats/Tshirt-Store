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
                'title' => "OrderController::refund() calls PayPal's refund API before acquiring its own row lock, risking a double refund",
                'description' => "OrderController::refund() (app/Http/Controllers/Api/OrderController.php:238-262) checks \$order->payment_status !== 'paid' on an unlocked read, then immediately calls \$payPal->refundCapture(\$order->paypal_transaction_id) at line 252 — well before the DB::transaction()/lockForUpdate() guard that only starts at line 260, which the code's own comment says exists specifically 'so two concurrent refund requests can't both restore the reserved stock.' That lock protects the DB state transition but does nothing to protect the PayPal call itself: two concurrent refund requests for the same order (two admin tabs, or one double-click landing before the frontend's refundingOrderId-based disable takes effect) can both pass the unlocked payment_status check and both fire a live, real-money PayPal refund for the same capture before either reaches the lock — only one wins the DB update, but PayPal has already been hit twice. Fix: acquire the row lock and re-check payment_status === 'paid' first, and only call payPal->refundCapture() once inside that locked, verified state, mirroring how cancel() already orders its checks.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "A delayed or duplicate PayPal DENIED webhook can silently revert an already-refunded order's status back to 'failed'",
                'description' => "PayPalWebhookController::markFailed() (app/Http/Controllers/Api/PayPalWebhookController.php:84-96) runs Order::where('paypal_order_id', \$id)->where('payment_status', '!=', 'paid')->update(['payment_status' => 'failed']) with no exclusion for orders already in a terminal 'refunded' state — only 'paid' is excluded. PayPal retries webhook delivery for days on end; if an admin refunds an order (payment_status becomes 'refunded') and a delayed or duplicate PAYMENT.CAPTURE.DENIED webhook for that same paypal_order_id arrives afterward, it passes the != 'paid' check and silently overwrites payment_status back to 'failed' with no SystemEvent logged and no admin notification, corrupting an already-resolved order's state. Fix: exclude 'refunded' (and any other terminal states) from markFailed()'s where clause, e.g. ->whereNotIn('payment_status', ['paid', 'refunded']).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin product update never regenerates the slug when the product name changes",
                'description' => "Admin\\ProductController::store() (app/Http/Controllers/Api/Admin/ProductController.php:45-50) derives a fresh, unique slug via uniqueSlug(\$data['name']) on create, but update() (:66-82) never recomputes it — the slug field is simply absent from update()'s fill data, so it's frozen at whatever the product was originally named. Since Product::getRouteKeyName() uses slug for every public product-detail URL, renaming e.g. 'Classic Tee' to 'Vintage Hoodie' via ProductManagement.jsx's edit form leaves the product permanently reachable only at the stale /products/classic-tee URL — the displayed title changes everywhere in the UI but the URL, sitemap entry, and any shared/bookmarked links stay stuck on the old name, confusing customers and looking broken for SEO. Fix: in update(), when the incoming name differs from the current one, recompute a unique slug via the same uniqueSlug() helper (excluding the product's own current slug from the uniqueness check so re-saving with an unchanged name is a no-op).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Wishlist add/remove endpoints carry no rate limiting, unlike every sibling authenticated write route",
                'description' => "POST and DELETE /api/products/{product}/wishlist (routes/api.php:116-117) have no throttle middleware at all, while every comparable authenticated write route in this app does: reviews use throttle:reviews, checkout uses throttle:checkout, account actions use throttle:account-security (all defined as named RateLimiter::for() limiters in app/Providers/AppServiceProvider.php, which defines no 'wishlist' limiter). Any authenticated account can script unbounded rapid-fire wishlist toggles with zero abuse protection, generating unchecked write load with none of the throttling this codebase otherwise applies consistently to every other write path. Fix: add a 'wishlist' RateLimiter (matching the shape/limits of the existing 'reviews' limiter) in AppServiceProvider and apply throttle:wishlist to both routes.",
                'agent_name' => 'Ops Agent',
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
        DB::table('project_tasks')->where('title', "OrderController::refund() calls PayPal's refund API before acquiring its own row lock, risking a double refund")->delete();
        DB::table('project_tasks')->where('title', "A delayed or duplicate PayPal DENIED webhook can silently revert an already-refunded order's status back to 'failed'")->delete();
        DB::table('project_tasks')->where('title', "Admin product update never regenerates the slug when the product name changes")->delete();
        DB::table('project_tasks')->where('title', "Wishlist add/remove endpoints carry no rate limiting, unlike every sibling authenticated write route")->delete();
    }
};
