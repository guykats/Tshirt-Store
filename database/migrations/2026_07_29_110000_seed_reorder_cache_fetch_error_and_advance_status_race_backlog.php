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
                'title' => "Admin image-gallery reorder silently bypasses catalog cache invalidation",
                'description' => "ProductImageController::reorder() (app/Http/Controllers/Api/Admin/ProductImageController.php:82-84) persists the new position for each image with ProductImage::where('id', \$id)->update(['position' => \$position]) — a query-builder mass update. ProductImage::booted() (app/Models/ProductImage.php:15-17) only flushes CatalogCache from the created/updated/deleted Eloquent model events, which Builder::update() never fires (unlike Model::save()). Every other write path in this controller (store, update, destroy) uses \$image->update()/->create()/->delete() and correctly triggers the flush. Failure scenario: an admin reorders a product's gallery in the admin panel — the cached ProductController::show response keeps serving the stale image order to shoppers for up to CatalogCache::TTL_SECONDS (300s), even though the reorder itself succeeded. Not a duplicate of the existing 'Catalog cache is invalidated globally by every stock change' task, which is about over-invalidation elsewhere — this is a distinct under-invalidation gap on a different write path. Fix: replace the mass update with a foreach that loads and saves each ProductImage model (or add an explicit CatalogCache::flush() call after the loop).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Orders.jsx conflates a failed order-history fetch with a genuinely empty history",
                'description' => "Orders.jsx:22-26's api.get('/api/orders').then(...).finally(() => setLoading(false)) has no .catch(). On a failed request, orders stays [] and the page renders the 'You have no orders yet' EmptyState, indistinguishable from a customer who truly never ordered. This is the customer-facing order-history page — a customer checking on a real order could wrongly conclude it never happened. Distinct from the already-tracked no-.catch() tickets, which are scoped to other pages (Catalog.jsx, ProductReviews.jsx, Dashboard widgets, Wishlist/account-addresses); none of those cover Orders.jsx. Fix: add a .catch() that sets an error state and render a distinct error message (not the empty-state copy), following the pattern already used elsewhere once those other tickets ship.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin product list and its designs dropdown have no error handling on their core fetches",
                'description' => "ProductManagement.jsx:88-96's loadProducts() (api.get('/api/admin/products', ...).then(...).finally(...)) has no .catch(), so a failed request leaves products empty and renders the admin catalog as if it holds zero products — the primary product-management surface, not a peripheral widget (distinct from the already-tracked 'Every Dashboard admin widget fetch has no .catch()' ticket, which is scoped to Dashboard.jsx, a different file). The same page has an identical gap on its designs dropdown fetch (ProductManagement.jsx:77-80, api.get('/api/designs', ...)) — a failed request there silently renders 'no approved designs' and blocks product creation with no indication it's a network failure rather than a genuine empty state. Fix: add .catch() handlers to both fetches with a visible error state distinct from the empty-list state.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "DesignSettings.jsx loads with no error feedback, risking silent overwrite of live homepage content",
                'description' => "DesignSettings.jsx:59-73's api.get('/api/site-settings').then(...).finally(() => setLoading(false)) has no .catch(). EMPTY_FORM (DesignSettings.jsx:21-29) seeds hero_tagline_en/he and hero_subheading_en/he as empty strings. If the initial fetch fails, the form silently renders with these blanks instead of the real live copy, with zero error indicator — an admin editing just the accent color and hitting Save (PATCH /api/site-settings, which requires these fields) risks submitting blank hero copy over the live homepage. loadTestimonials() (DesignSettings.jsx:77-82) has the identical missing-.catch() gap. Not covered by any existing no-.catch() ticket (all target other pages), and this one carries a real data-loss angle beyond a cosmetic empty state. Fix: add .catch() handlers to both fetches, block the save action (or warn clearly) if the initial settings load failed.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "OrderController::advanceStatus lacks the row-lock race guard cancel()/refund() use, risking duplicate shipment/delivery emails",
                'description' => "OrderController::advanceStatus() (app/Http/Controllers/Api/OrderController.php:135-191) reads \$order->status from the already-loaded, unlocked model, computes nextFulfillmentStatus(), persists it, and unconditionally sends OrderShippedMail/OrderDeliveredMail (:184-188) — with no DB::transaction()/lockForUpdate() re-check under lock, unlike cancel() (:193-232) and refund() (:239-296), which explicitly re-lock and re-check status before acting. Failure scenario: two concurrent calls to POST /orders/{order}/advance-status for the same order (e.g. two admin tabs open, or a slow request retried) can both read the same starting status, both compute the same nextStatus, and both send the customer a duplicate shipped/delivered notification. Distinct from the already-tracked webhook-vs-buyer-capture duplicate-email race (CheckoutController::capture vs. the PayPal webhook), which only covers payment confirmation, not fulfillment-status advancement. Fix: wrap advanceStatus() in DB::transaction() with Order::where('id', \$id)->lockForUpdate()->first(), re-checking status under the lock before writing or emailing, matching cancel()'s existing pattern.",
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
        DB::table('project_tasks')->where('title', "Admin image-gallery reorder silently bypasses catalog cache invalidation")->delete();
        DB::table('project_tasks')->where('title', "Orders.jsx conflates a failed order-history fetch with a genuinely empty history")->delete();
        DB::table('project_tasks')->where('title', "Admin product list and its designs dropdown have no error handling on their core fetches")->delete();
        DB::table('project_tasks')->where('title', "DesignSettings.jsx loads with no error feedback, risking silent overwrite of live homepage content")->delete();
        DB::table('project_tasks')->where('title', "OrderController::advanceStatus lacks the row-lock race guard cancel()/refund() use, risking duplicate shipment/delivery emails")->delete();
    }
};
