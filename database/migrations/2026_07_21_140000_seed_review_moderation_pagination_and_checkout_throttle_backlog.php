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
                'title' => 'Admin review moderation (delete abusive/fake reviews)',
                'description' => 'ReviewController (app/Http/Controllers/Api/ReviewController.php) has index/eligibility/store but no destroy or admin listing at all — once a Review row exists (unique per product+user, enforced at the DB level) there is no code path in the whole app to remove one, even though it renders publicly on ProductDetail.jsx and feeds the average_rating shown on the product page and Catalog.jsx home stats. Add an admin-only DELETE /api/products/{product}/reviews/{review} route (authorize via a policy, following the pattern OrderController/DesignController already use) that removes the review, logs a SystemEvent (\'review.deleted\', matching the review.deleted-style naming already used for product_image.deleted etc.), and surface a delete action in a small admin reviews panel (new ProductManagement.jsx section or a dedicated /dashboard/reviews page — reuse the ProjectProgress.jsx-style paginated table pattern). Write feature tests: non-admin gets 403, admin can delete a review, deleting recalculates the product\'s average_rating on the next GET, and deleting a non-existent review 404s.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Dashboard admin queues silently miss orders/designs beyond page 1',
                'description' => 'Dashboard.jsx\'s loadFulfillmentOrders/loadOrders/loadDesigns call api.get(\'/api/orders\') and api.get(\'/api/designs\', {params:{status:\'pending_approval\'}}) with no page param and then filter client-side, but OrderController::index and DesignController::index both paginate 20-at-a-time (app/Http/Controllers/Api/OrderController.php, app/Http/Controllers/Api/DesignController.php) — once the store has more than 20 total orders or more than 20 pending designs, older ones needing fulfillment/refund/approval become invisible on the dashboard with no error or "load more" affordance, they just silently stop showing up. Fix by either looping through meta.last_page (like the just-shipped AuditLog.jsx does) or bumping these specific admin queue queries to a much larger page size / a dedicated unpaginated admin-only endpoint, whichever keeps response payload reasonable. Write a feature/frontend test proving an order on page 2 of the admin index (21st+ order) still surfaces as fulfillable/refundable on the dashboard after the fix, where it did not before.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Order emails hand-format currency instead of using locale-aware formatting',
                'description' => 'resources/views/emails/order-confirmation.blade.php (and order-shipped/order-delivered/order-refunded.blade.php) render totals as `{{ $order->currency }} {{ number_format($order->total_amount, 2) }}` — the exact hand-rolled pattern the "Locale-aware price and number formatting for Hebrew" task just eliminated from resources/js, but that task only touched the frontend, not these server-rendered Blade emails, and the templates already switch dir=rtl/lang based on app()->getLocale() so a Hebrew-locale customer\'s emails still show "USD 19.99"-style formatting instead of a properly localized amount. Add a small App\\Services\\ or Blade helper using PHP\'s intl NumberFormatter (new NumberFormatter($locale, NumberFormatter::CURRENCY)) mirroring resources/js/lib/formatPrice.js\'s behavior, wire it into all four order emails, and set the mail\'s locale from the order\'s user/guest locale (fall back to app default) before rendering. Write a feature test asserting an order confirmation email rendered for a user with locale=he contains Hebrew-formatted currency, distinct from the en rendering.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Illustrated empty states for Wishlist, Orders, and Catalog no-results',
                'description' => 'Wishlist.jsx\'s empty state, Orders.jsx\'s orders_empty, and Catalog.jsx\'s catalog_empty/catalog_no_search_results all render as a single bare <p> of text, while the 404 page (NotFound.jsx) and several hero sections already use the DesignArt component\'s line-art motifs (see DesignArt.jsx\'s `label` prop convention) to make an otherwise-blank page feel intentional rather than broken. Add a small reusable EmptyState component (icon/motif + heading + body, following DesignArt\'s accessible label/aria pattern) and use it for those three empty states plus any other bare-text "no X yet" spot found in the same sweep, picking a motif that\'s thematically fitting for each (e.g. a hamsa or star-of-david outline, not literally an empty box icon, matching the brand\'s existing visual language). Every new string needs a real English and Hebrew entry in resources/js/i18n/index.js, and the motif SVG needs the same aria-label/role="img" treatment DesignArt already uses elsewhere. Add a Playwright screenshot of at least one of these empty states and a lightweight Vitest test confirming the EmptyState renders with an accessible name.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
            ],
            [
                'title' => 'Rate-limit the guest-reachable checkout endpoint',
                'description' => 'POST /api/checkout (routes/api.php, App\\Http\\Controllers\\Api\\CheckoutController::store) is intentionally reachable without a session for guest checkout, but unlike every other unauthenticated write endpoint in this app (register, login, forgot-password, reset-password all have a dedicated throttle: entry in AppServiceProvider::boot()) it has zero throttle middleware — an anonymous caller can hammer it to brute-force coupon codes (see the just-shipped CouponService validation), create unlimited guest User rows, or spam PayPal order-creation calls. Add a RateLimiter::for(\'checkout\', ...) entry in app/Providers/AppServiceProvider.php following the exact existing pattern (see \'register\'/\'login\'), apply ->middleware(\'throttle:checkout\') to the /checkout route, and pick a limit generous enough not to block a real shopper retrying a failed card/coupon a few times but tight enough to stop scripted abuse (e.g. the same order of magnitude as \'register\'). Write a feature test asserting the Nth+1 request within the window gets a 429 while requests under the limit still succeed.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'chore',
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
            'Admin review moderation (delete abusive/fake reviews)',
            'Dashboard admin queues silently miss orders/designs beyond page 1',
            'Order emails hand-format currency instead of using locale-aware formatting',
            'Illustrated empty states for Wishlist, Orders, and Catalog no-results',
            'Rate-limit the guest-reachable checkout endpoint',
        ])->delete();
    }
};
