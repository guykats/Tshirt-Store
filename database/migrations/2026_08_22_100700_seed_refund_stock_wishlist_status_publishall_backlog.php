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
                'title' => 'OrderController::refund restores stock for orders that have already shipped or been delivered, creating phantom inventory',
                'description' => "app/Http/Controllers/Api/OrderController.php's refund() only ever checks payment_status ('paid' on entry, then re-checked 'paid' on a row-locked copy inside the DB::transaction) before calling \$this->stock->restore(\$locked), which increments stock_quantity for every item on the order. It never checks \$order->status. Contrast with cancel(), which is gated by Order::isCancellable() (app/Models/Order.php) — explicitly restricted to the pending_approval/approved statuses precisely because once an order is processing/shipped/delivered the physical stock has already left the building. OrderPolicy::refund() only requires the actor to be an admin, with no status guard either. So an admin refunding an already-shipped or delivered order (a legitimate real-world action — money still gets refunded to the customer) silently adds that already-shipped merchandise's quantity back into stock_quantity, letting the storefront oversell inventory that doesn't physically exist in the warehouse. Fix: only call stock restore when the order hasn't actually shipped (mirror isCancellable()'s status gate, or make restoration conditional on order status separately from the payment-status refund logic).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'WishlistController has no product-status gate, letting customers wishlist and permanently see draft or archived products',
                'description' => "app/Http/Controllers/Api/WishlistController.php's store() and index() never check \$product->status. The route (routes/api.php, POST /api/products/{product}/wishlist) binds {product} by slug, and Product has no global scope restricting queries to active products — contrast with the public ProductController::show(), which explicitly does abort_unless(\$product->status === 'active', 404). Anyone who learns a draft or archived product's slug (a shared preview link, a guessed URL, or a product archived after being wishlisted) can add or keep it on their wishlist, and WishlistController::index() eager-loads and returns it via WishlistItemResource indefinitely — exposing an unpublished product's name/description/price on the customer-facing Wishlist page before, or after, the admin intended it to be public. Fix: scope wishlist store()/index() (or the underlying query) to active products, same as the public catalog and product-detail endpoints already do.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Discover tab's \"Publish all kept\" bulk action marks kept suggestions from EVERY loaded batch as published, even though the backend only publishes the latest batch",
                'description' => "Backend: app/Http/Controllers/Api/DesignSuggestionController.php's publishAll() explicitly scopes to ->where('batch_date', \$latestBatchDate) — by design it only promotes kept suggestions from the single most recent batch. But DesignSuggestionController::index() (GET /api/design-suggestions, which Discover.jsx loads from) orders by batch_date desc with no batch filter and paginates 20 — the same gap already tracked separately as 'Discover tab's suggestion feed silently loses earlier same-day batches once more than 20 exist' — meaning suggestions from older batches ARE routinely visible in the grid alongside the latest batch. resources/js/pages/Discover.jsx's handlePublishAll() then does setSuggestions((current) => current.map((s) => (s.status === 'kept' ? {...s, status: 'published'} : s))) — flipping every locally-loaded 'kept' card to 'published' regardless of batch_date, while the success toast correctly reports only the real publishedCount from the response. An admin who kept suggestions from an older batch that's still visible in the loaded page sees those cards silently marked 'published' in the UI, even though the backend never created a Design row for them and their real status is still 'kept' — they drop out of the admin's attention as handled when they never were. Fix: only optimistically flip suggestions whose batch_date matches the batch that was actually published (or refetch instead of locally patching).",
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
            'OrderController::refund restores stock for orders that have already shipped or been delivered, creating phantom inventory',
            'WishlistController has no product-status gate, letting customers wishlist and permanently see draft or archived products',
            "Discover tab's \"Publish all kept\" bulk action marks kept suggestions from EVERY loaded batch as published, even though the backend only publishes the latest batch",
        ])->delete();
    }
};
