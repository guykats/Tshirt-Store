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
                'title' => 'Wishlist.jsx treats a failed wishlist fetch the same as a genuinely empty wishlist',
                'description' => "resources/js/pages/Wishlist.jsx:20-24 does api.get('/api/wishlist').then(...).finally(...) with no .catch(), so a network/500 error leaves items at [] and renders the exact same 'your wishlist is empty' EmptyState as a real empty list. Catalog.jsx and ProductDetail.jsx both already got dedicated fetch-error states for this exact failure mode (see tasks #106 and #109); Wishlist.jsx never did, so a shopper whose wishlist fetch actually failed has no way to tell it apart from having saved nothing, and no retry affordance. Fix by adding an error state (set on .catch, cleared on success) and a distinct error UI, matching the pattern already used on Catalog/ProductDetail.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Wishlist.jsx's loading state is a raw, untranslated '…' with no screen-reader announcement",
                'description' => "resources/js/pages/Wishlist.jsx:35 renders {loading && <p className=\"text-ink-soft\">…</p>} — a literal ellipsis glyph with no i18n key and no role=\"status\"/aria-live, while Catalog.jsx and ProductDetail.jsx use the purpose-built Skeleton components (with the accessible loading announcement added in task #174) for the same moment. A screen-reader user on the Wishlist page gets no indication anything is loading, and a Hebrew-locale user sees an untranslated fragment. Fix by swapping the raw '…' paragraph for the same Skeleton/loading-announcement pattern already established elsewhere.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'OrderCard.jsx renders order dates in the browser default locale instead of the site language',
                'description' => "resources/js/components/OrderCard.jsx:38 calls new Date(order.created_at).toLocaleDateString() with no locale argument, even though the same component already destructures i18n and correctly locale-formats the order total two lines later at line 81 via formatPrice. Every other locale-sensitive timestamp in the app was fixed for this exact bug in tasks #172 and #176; OrderCard (used on both the Orders list and Track Order page) was missed. A Hebrew-preferring customer sees their own order dates rendered in English/browser-default formatting. Fix by passing i18n.language (mapped to a real locale tag) into toLocaleDateString, matching the pattern used elsewhere.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "ProductDetail.jsx's Buy Now button stays fully clickable on a selected variant that is out of stock",
                'description' => "resources/js/pages/ProductDetail.jsx:223-230 — the Buy Now Link's enabled/disabled className branch only checks selected truthiness (pointer-events-none bg-line ... applied when !selected), never selected.stock_quantity, even though the label two lines below already switches to t('checkout_out_of_stock') when selected.stock_quantity === 0. Because the size buttons are only disabled per the *currently selected* color (line 204), switching color after a size is already picked can leave `size` state pointing at a size/color combo with 0 stock: the button then reads 'Out of stock' in its own label while still rendering with the full clickable bg-ink style and a live href into Checkout. Fix by adding the same selected.stock_quantity === 0 check used for the label into the className/pointer-events branch so the CTA is genuinely disabled, not just relabeled.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Checkout.jsx's quantity field is capped at a fixed 20 instead of the selected variant's actual stock",
                'description' => "resources/js/pages/Checkout.jsx:258-267 hardcodes max=\"20\" on the quantity <input>, regardless of the chosen variant's stock_quantity (already available on the same options list rendered two lines above at 251-252, which already disables options with stock_quantity === 0). A shopper who selects a variant with, say, 2 left can still type up to 20 in the field and only discover the real limit when the order-creation POST fails server-side. Fix by binding max to the selected variant's stock_quantity (falling back to the current 20 only when nothing is selected yet) and clamping/validating client-side before submit.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'PayPal capture and webhook confirmation can both mark the same order paid, risking a duplicate confirmation email',
                'description' => "app/Http/Controllers/Api/CheckoutController.php:251 (capture()) and app/Http/Controllers/Api/PayPalWebhookController.php:62 (markPaid()) both do a plain if (\$order->payment_status !== 'paid') check followed by a separate update(), with no row lock — unlike cancel()/refund() in the same CheckoutController, which explicitly lockForUpdate() to close this exact class of race. A browser capture() call landing at nearly the same moment as PayPal's webhook delivery for the same order can both pass the stale check before either write commits, each proceeding to send its own OrderConfirmationMail and log its own 'order.paid' SystemEvent. Fix by wrapping both paths' read-check-write in a DB transaction with lockForUpdate() on the order row, matching the existing cancel()/refund() pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Wishlist add/remove endpoints have no rate limiting despite being public-write endpoints',
                'description' => "routes/api.php:127-128 (POST and DELETE /api/products/{product}/wishlist) have no throttle middleware at all, unlike every comparable public-write endpoint: reviews use throttle:reviews (task #85), and account-security actions were rate-limited in task #110. A single authenticated session can script unlimited wishlist_items inserts/deletes against any product with no backoff. Fix by adding a dedicated throttle (mirroring the reviews rate limiter's shape/limits) to both routes.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Admin coupon form accepts an expires_at date that has already passed, with no validation error',
                'description' => "app/Http/Controllers/Api/Admin/CouponController.php:94 validates expires_at as only ['nullable', 'date'], with no after:now (or after:today) rule. An admin can create or edit a coupon with a past expiry date and get no warning at all — the coupon saves successfully but is dead on arrival, silently unredeemable from the moment it's created. Fix by adding an after:today (or after:now) rule to the expires_at validation on both create and update, with a clear inline error surfaced in CouponManagement.jsx.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Deploy pipeline never verifies the site actually came back up after deploying',
                'description' => ".github/workflows/deploy.yml swaps code, runs migrations, and re-caches config, then just exits — it never calls the already-built GET /api/health endpoint (see HealthController, from task #57) afterward. A crashed PHP-FPM pool, a bad .env value, or a failed view:cache/config:cache step ships as a green 'Deploy' run with no automated check that the site is actually serving requests; the maintenance-mode trap only guarantees php artisan up ran, not that the app behind it is healthy. Fix by adding a post-deploy curl against /api/health that fails the workflow (and ideally re-triggers rollback guidance) if it doesn't return a healthy response.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Deploy workflow's SSH steps have no command_timeout, so a hung remote command can wedge the site in maintenance mode indefinitely",
                'description' => ".github/workflows/deploy.yml's two appleboy/ssh-action@v1.2.0 steps set no command_timeout. The maintenance-mode window relies entirely on the remote bash script's own trap 'php artisan up || true' EXIT to bring the site back — but that trap only fires if the remote script itself exits. A stalled git fetch, a composer install waiting on a network hang, or a php artisan migrate blocked on a DB lock can leave the SSH session (and the remote script) hanging with no timeout on either side, parking the live site behind php artisan down with no automatic recovery. Fix by adding an explicit command_timeout (and a bounded timeout on the workflow job itself) so a hang fails loudly instead of silently wedging production.",
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
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Wishlist.jsx treats a failed wishlist fetch the same as a genuinely empty wishlist',
            "Wishlist.jsx's loading state is a raw, untranslated '…' with no screen-reader announcement",
            'OrderCard.jsx renders order dates in the browser default locale instead of the site language',
            "ProductDetail.jsx's Buy Now button stays fully clickable on a selected variant that is out of stock",
            "Checkout.jsx's quantity field is capped at a fixed 20 instead of the selected variant's actual stock",
            'PayPal capture and webhook confirmation can both mark the same order paid, risking a duplicate confirmation email',
            'Wishlist add/remove endpoints have no rate limiting despite being public-write endpoints',
            'Admin coupon form accepts an expires_at date that has already passed, with no validation error',
            'Deploy pipeline never verifies the site actually came back up after deploying',
            "Deploy workflow's SSH steps have no command_timeout, so a hung remote command can wedge the site in maintenance mode indefinitely",
        ])->delete();
    }
};
