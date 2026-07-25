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
                'title' => "Dashboard.jsx's design/order approve, reject, and agent-status actions have no error handling at all",
                'description' => "resources/js/pages/Dashboard.jsx's approveDesign, rejectDesign, approveOrder (lines ~108-125), and updateAgent (~168-172) each do a bare `await api.post(...)` / `await api.patch(...)` with no try/catch and no loading/disabled guard, then immediately call their reload functions. Distinct from ticket 135 ('Admin refund action has zero error feedback'), which is specifically about refundOrder (which already has try/finally, just no catch) and from the general fetch-error tickets (122/125/140/146/etc.), which are all about *loading* data, not *mutating* it. Failure scenario: an admin clicks 'Approve' on a design or order while the request 500s (or the network drops) — the promise rejects unhandled, no error message appears, the list doesn't refresh, and the admin has no way to know the action didn't take effect; a second click can also double-submit since there's no in-flight guard. Fix: wrap each in try/catch (surfacing failure via the same shippingErrors/refundingOrderId-style state pattern already used elsewhere in this file) and guard against concurrent double-submission.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'POST /api/webhooks/paypal has zero rate limiting, unlike every other route in the file',
                'description' => "routes/api.php:64 defines Route::post('/webhooks/paypal', [PayPalWebhookController::class, 'handle']); with no throttle middleware whatsoever — contrast with every other route in the same file, all of which carry at least one of throttle:catalog-read, throttle:checkout, throttle:login, throttle:register, throttle:health-check, throttle:visioner-chat, etc. PayPalWebhookController::handle (app/Http/Controllers/Api/PayPalWebhookController.php) does real work per hit: looks up the Order, and on a capture event calls PayPalClient (app/Services/PayPalClient.php:128-136) which makes an outbound API call to PayPal to verify the capture. Since this endpoint is necessarily public (PayPal itself calls it, unauthenticated), it's the one place in the app anyone can trigger unlimited real outbound PayPal API calls and DB writes at zero cost, purely by POSTing to a known, undocumented-but-guessable URL. Fix: add a dedicated named limiter (e.g. throttle:paypal-webhook) generous enough for legitimate PayPal retry volume but bounded, the same pattern already used for every other public route.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'orders.payment_status has no database index despite being filtered on every abandoned-order sweep and home-stats query',
                'description' => "database/migrations/2026_07_20_121000_add_indexes_to_products_and_orders_tables.php added indexes for orders.status and orders.user_id, but not orders.payment_status — even though it's filtered just as heavily: ExpireAbandonedOrders::handle (app/Console/Commands/ExpireAbandonedOrders.php:34) runs ->where('payment_status', '!=', 'paid') as part of a command wired to run every 15 minutes forever (per bootstrap/app.php's withSchedule()) over the full, ever-growing orders table; HomeStatsController (app/Http/Controllers/Api/HomeStatsController.php:24,31) runs two more payment_status = 'paid' queries on every homepage load path; PayPalWebhookController (line 94) and ReviewController (line 186) filter on it too. As order volume grows this becomes a full table scan on a recurring background job and a customer-facing page, the same category of problem the 07-20 migration was written to fix for status/user_id — payment_status was simply missed. Fix: a migration adding \$table->index('payment_status') to orders (or better, a composite (payment_status, status) index if query plans show the abandoned-order sweep benefits from it).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Admin variant duplicate-color check is case- and whitespace-sensitive, letting near-identical colors slip through as distinct variants',
                'description' => "app/Http/Controllers/Api/Admin/ProductVariantController.php's duplicateCombo (lines ~118-135) compares ->where('color', \$data['color']) as a raw string equality against the existing (product_id, size, color) rows, with no normalization. 'Black', 'black', and 'Black ' (trailing space) all pass validation as distinct colors for the same product/size, creating multiple variants that a shopper's color swatch picker (resources/js/components/ColorSwatch.jsx, which maps color names to swatches) cannot meaningfully distinguish — they render as duplicate-looking or identically-labeled options. This is a data-integrity gap distinct from the already-tracked GarmentMockup two-hardcoded-colors ticket (121), which is about mockup rendering, not variant creation validation. Fix: normalize color (trim + consistent casing, e.g. via a shared normalizeColor() helper) before both the duplicateCombo existence check and the actual insert/update, so 'Black' and 'black ' are treated as the same variant.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'GET /sitemap.xml has no rate limiting and no caching, unlike every other public read in the app',
                'description' => "routes/web.php only registers Route::get('/sitemap.xml', [SitemapController::class, 'index']); and Route::get('/{any}', ...) for the SPA catch-all — neither the web middleware group nor this route applies any throttle. bootstrap/app.php's withRouting only wires routes/web.php and routes/api.php with no additional throttle on the web group, so this route is completely unlimited. SitemapController::index (app/Http/Controllers/SitemapController.php:16-34) runs a real, uncached Product::query()->where('status','active')->get(['slug','updated_at']) on every single hit, in contrast to the public catalog/product-detail endpoints (app/Http/Controllers/Api/ProductController.php), which wrap the equivalent query in CatalogCache::remember. As the catalog grows, /sitemap.xml is a public, unauthenticated, cache-free full-table query any crawler or bot can hit at unlimited rate. Fix: add a throttle middleware to this route (mirroring throttle:catalog-read) and wrap its product query in CatalogCache the same way ProductController::index already does, invalidated the same way on stock/status change.",
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
        DB::table('project_tasks')->whereIn('title', [
            "Dashboard.jsx's design/order approve, reject, and agent-status actions have no error handling at all",
            'POST /api/webhooks/paypal has zero rate limiting, unlike every other route in the file',
            'orders.payment_status has no database index despite being filtered on every abandoned-order sweep and home-stats query',
            'Admin variant duplicate-color check is case- and whitespace-sensitive, letting near-identical colors slip through as distinct variants',
            'GET /sitemap.xml has no rate limiting and no caching, unlike every other public read in the app',
        ])->delete();
    }
};
