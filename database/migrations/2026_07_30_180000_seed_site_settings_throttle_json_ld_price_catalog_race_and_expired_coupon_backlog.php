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
                'title' => '/api/site-settings has zero rate limiting, unlike every sibling public bootstrap endpoint',
                'description' => "routes/api.php:54 defines Route::get('/site-settings', [SiteSettingController::class, 'show']); with no throttle middleware at all. Every other unauthenticated public read it sits alongside is wrapped in a limiter — /api/products, /api/products/{product}, /api/products/{product}/reviews (lines 46-50) and /api/home-stats, /api/testimonials (lines 59-62) all use 'throttle:catalog-read' (see AppServiceProvider), and /api/health gets its own 'throttle:health-check'. SiteSettingController::show() is fetched by SiteSettingsProvider (resources/js/lib/SiteSettingsContext.jsx) on literally every SPA page load — it's the most fundamental bootstrap call in the app — yet it's the one public endpoint a scraper/bot can hammer at unlimited rate with no cost, unlike its throttled siblings immediately above and below it in the same route file. Fix: wrap it in the same 'throttle:catalog-read' middleware group used by the neighboring public routes.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override",
                'description' => "resources/js/pages/ProductDetail.jsx builds the page's schema.org/Product JSON-LD with a single fixed Offer using price: Number(product.base_price).toFixed(2) (around line 59), regardless of which variant is selected. But ProductVariantResource exposes a per-variant price_override that can legitimately differ from base_price — the same mechanism already flagged elsewhere in the backlog as silently changing what a shopper is charged at checkout. Failure scenario: a product has a variant with price_override set higher or lower than base_price; Google (or any crawler consuming the structured data for rich results / Merchant Center) indexes the JSON-LD offer at the wrong price. When a shopper actually selects that variant and checks out at the real, different price, the mismatch between advertised structured-data price and actual charge is exactly the kind of discrepancy Google Merchant Center flags and can disapprove a listing for — a distinct, SEO/compliance-facing consequence from the already-known 'shopper isn't shown the override before checkout' issue. Fix: compute offers.price from the currently-selected variant (falling back to base_price only when no variant is selected), the same way the visible on-page price already does at line 156.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Catalog.jsx's search/sort/page fetch has no request sequencing, so a slower in-flight response can overwrite newer results",
                'description' => "resources/js/pages/Catalog.jsx:33-42's useEffect fires api.get('/api/products', {...}) on every [page, search, sort] change with no AbortController and no 'ignore this response if it's stale' guard — whichever request resolves last wins and calls setProducts/setMeta, regardless of whether it corresponds to the current search/sort/page state. Failure scenario: a shopper types 'menorah' (debounced per handleSearchInput) then quickly changes the query to 'star' before the first request returns; on a variable-latency connection (mobile network, or simply a query that happens to touch more rows and takes longer server-side) the 'menorah' response can arrive after the 'star' response, silently replacing the correct 'star' results with stale 'menorah' ones while the search box and URL both still show 'star'. The same race applies to rapid pagination or sort-change clicks. Fix: track a request id or AbortController per effect run and only apply the response if it matches the latest triggered request.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Admin coupon create/update accepts an expires_at date already in the past, silently creating a dead-on-arrival coupon",
                'description' => "app/Http/Controllers/Api/Admin/CouponController.php:94 validates 'expires_at' => ['nullable', 'date'] with no after:today / after_or_equal:today constraint, and CouponManagement.jsx has no client-side minimum-date check on the date input either. Failure scenario: an admin creating a holiday promo mistypes or mispicks a past date (or reuses a prior year's date from a copy-pasted value); CouponController::store() happily returns 201 and the coupon appears active in the list. The very first customer who tries it hits Coupon::isExpired() returning true and gets 'This coupon code has expired' at checkout — with nothing on the admin coupon list or form having ever surfaced that the code was unusable from the moment it was saved. Fix: add an after_or_equal:today validation rule (or a same-day allowance) to expires_at server-side, and reflect it as a min on the date input client-side.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "VisionerChatController::store never catches AnthropicClient's RuntimeException, crashing the chat with a raw 500 instead of a clean error",
                'description' => "app/Http/Controllers/Api/VisionerChatController.php:45 calls \$anthropic->converse(...) with no surrounding try/catch. AnthropicClient::send() (app/Services/AnthropicClient.php:85-97) explicitly wraps its HTTP call in try { ... } catch (RequestException \$e) { throw new RuntimeException(...); } — i.e. it's designed to bubble a RuntimeException up to its caller on any Anthropic API failure (rate limit, timeout, invalid/missing key, malformed response). Every other external-API integration in this codebase catches that kind of exception at the controller boundary and returns a clean, user-facing error — e.g. CheckoutController catches PayPal's RuntimeException and returns a 502 with a translated message, and PmAgentAutomationController::toggle() does the same for GitHubActionsClient. VisionerChatController::store() has no equivalent — a rate-limited or momentarily-down Anthropic API surfaces as an unhandled 500 to the admin mid-conversation, on top of the already-known orphaned-user-message issue on the same code path (the user's VisionerChatMessage is already created at line 32, before the unguarded call at line 45). Fix: wrap the converse() call in a try/catch (RuntimeException \$e) and return a translated, actionable error response instead of letting it propagate.",
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
        DB::table('project_tasks')->where('title', '/api/site-settings has zero rate limiting, unlike every sibling public bootstrap endpoint')->delete();
        DB::table('project_tasks')->where('title', "ProductDetail.jsx's JSON-LD structured data prices every variant at base_price, ignoring price_override")->delete();
        DB::table('project_tasks')->where('title', "Catalog.jsx's search/sort/page fetch has no request sequencing, so a slower in-flight response can overwrite newer results")->delete();
        DB::table('project_tasks')->where('title', 'Admin coupon create/update accepts an expires_at date already in the past, silently creating a dead-on-arrival coupon')->delete();
        DB::table('project_tasks')->where('title', "VisionerChatController::store never catches AnthropicClient's RuntimeException, crashing the chat with a raw 500 instead of a clean error")->delete();
    }
};
