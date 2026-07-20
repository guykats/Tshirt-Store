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
                'title' => 'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed',
                'description' => 'CheckoutController::store() increments a coupon\'s redemptions_count the moment an order is placed (`$coupon->increment(\'redemptions_count\')`, app/Http/Controllers/Api/CheckoutController.php:134) — before payment is even captured, the same "reserve first" pattern already known to be a problem for stock (see the now-fixed stock-restore task). But OrderStockService::restore() (app/Services/OrderStockService.php), the shared helper that OrderController::cancel()/refund() and the ExpireAbandonedOrders scheduled command all call, only restores product_variants.stock_quantity — it never touches the coupon or decrements redemptions_count back down. Effect: a coupon with max_redemptions=50 permanently loses one of its 50 uses every time an order that happened to use it is cancelled, refunded, or auto-expired for never being paid, even though that redemption never actually benefited a completed sale. Add a coupon-redemption release step (look up the coupon by `discount_code` on the order, row-lock it the same way lockAndValidate() does, decrement redemptions_count with a floor of 0) alongside the stock restore, either inside OrderStockService::restore() itself or a sibling step called from the same three call sites. Write feature tests: cancelling/refunding/auto-expiring an order that used a coupon restores its redemptions_count, and an order placed without a coupon leaves other coupons\' counts untouched.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Every shared page URL renders the same static homepage Open Graph preview — no per-product image/title when a product link is shared',
                'description' => 'routes/web.php\'s catch-all SPA route (`Route::get(\'/{any}\', fn () => view(\'app\'))->where(\'any\', \'.*\')`) always renders the exact same resources/views/app.blade.php with hardcoded og:title/og:description/og:image/og:url values (the site-level "Jewish Identity, Understated" copy and public/og-image.png) regardless of the requested path — a WhatsApp/Twitter/Facebook share of a specific product page (e.g. /products/aleph-tee) shows the generic homepage preview, not that product\'s actual name, description, or photo, even though ProductDetail.jsx already knows how to build correct per-product JSON-LD client-side (see the `b()`/structured-data effect in the compiled bundle) — that JSON-LD update happens too late for crawlers, which is exactly the app.blade.php comment\'s stated reasoning for keeping OG tags static ("most social crawlers don\'t execute JavaScript"). That reasoning is correct but only rules out a client-side fix — it does not rule out a server-side one, and Laravel is already rendering this route server-side per request. Give the catch-all route (or a small dedicated controller) real per-request OG data: match `/products/{slug}` (and any other content routes worth it, e.g. `/designs/{slug}` if one exists) against the Product/Design table server-side, and pass the real name/description/first product image into app.blade.php\'s meta tags, falling back to today\'s site-level defaults for every other path. Verify by curling a product URL directly (no JS) and confirming the returned HTML\'s og:title/og:image differ from the homepage\'s.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them',
                'description' => 'The admin order-search feature just shipped (OrderController::index()\'s `search` param, Dashboard.jsx\'s Order Search section) deliberately scoped to matching the order\'s user\'s name/email only, per its task description — but in practice a support request or refund dispute more often references the order number itself (e.g. "ORD-1234" from a confirmation email) than the customer\'s exact name/email spelling, and there is no way to search by it at all right now. Separately, the search results table is read-only: an admin who finds the right order via search still has no click-through to view its detail, approve it, advance its fulfillment status, or refund it — they have to separately hunt for the same order in one of the fixed status-bucket sections below (Fulfillment/Refunds) to actually act on it, which defeats some of the point of a general search. Extend the `search` param\'s whereHas/orWhere to also match `order_number` directly (still admin-only, still SQLite/MySQL-portable), and add a "View" link (or the same inline approve/advance/refund actions the other sections already have, reusing their handlers) to each row in Dashboard.jsx\'s Order Search results table. Add feature tests covering an order-number search matching by itself (no customer name/email match needed).',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Nothing in deploy.yml actually installs the crontab entry that bootstrap/app.php\'s comment claims exists — scheduled jobs may never run in production',
                'description' => 'bootstrap/app.php\'s withSchedule() block has a comment stating "deploy.yml wires that up via an idempotent crontab entry on the Hostinger host" to explain how `php artisan schedule:run` gets invoked every minute in production — but `grep -n \'crontab\\|cron\' .github/workflows/deploy.yml` returns zero matches, and reading the full deploy.yml SSH script (git reset --hard, composer install, migrate --force, storage:link, then a second SSH step for config/route/view caching) confirms there really is no step anywhere that runs `crontab` or otherwise registers schedule:run. If a crontab entry was set up by hand directly on the Hostinger box outside of git at some point, it is completely undocumented and not reproducible from a clean deploy (e.g. a server migration or box rebuild would silently lose it) — and if it was never set up at all, then both scheduled jobs that currently exist (app:backup-database daily at 03:00, app:expire-abandoned-orders every 15 minutes, per bootstrap/app.php) have never actually executed in production despite having passing tests and being marked done on the board, meaning nightly DB backups may not exist and abandoned-order stock/coupon reservations may never be released. Verify directly (SSH in or ask the owner to confirm `crontab -l` on the production host) whether the cron entry exists; if not, add an idempotent step to deploy.yml\'s existing SSH script that installs a `* * * * * cd {path} && php artisan schedule:run >> /dev/null 2>&1` crontab line only if it\'s not already present (e.g. via `(crontab -l 2>/dev/null | grep -F "schedule:run" || (crontab -l 2>/dev/null; echo "... schedule:run ...") | crontab -)`), and correct or remove the misleading comment in bootstrap/app.php once it\'s actually true.',
                'agent_name' => 'Ops Agent',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Cancelling, refunding, or auto-expiring an order never releases the coupon redemption it consumed',
            'Every shared page URL renders the same static homepage Open Graph preview — no per-product image/title when a product link is shared',
            'Admin order search (Dashboard.jsx) only matches customer name/email, not the order number, and results have no way to act on them',
            'Nothing in deploy.yml actually installs the crontab entry that bootstrap/app.php\'s comment claims exists — scheduled jobs may never run in production',
        ])->delete();
    }
};
