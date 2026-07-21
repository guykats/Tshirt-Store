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
                'title' => 'ProductDetail.jsx never handles a failed product fetch — infinite skeleton, not an error state',
                'description' => 'ProductDetail.jsx fetches api.get(`/api/products/${slug}`) with no .catch, and product starts null, so the render guard (`if (!product) return <ProductDetailSkeleton />`) only ever checks for absence, not failure. A deleted/mistyped/stale product slug, or any transient network/500 error, leaves the shimmering skeleton loader rendering forever with no path back to the catalog. Add a notFound/error state, caught from the fetch rejection, and render an EmptyState-style message with a link back to the catalog when it fires. Feature/component test: mock a 404 response for the product fetch and assert the skeleton is replaced by an error message plus a working catalog link, not left rendering indefinitely.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Checkout and account address forms hardcode country: US with no country field ever rendered',
                'description' => 'Checkout.jsx and AccountSettings.jsx both default country: \'US\' in their address form state, but the rendered field list (full_name, line1, line2, city, state, postal_code, phone) never includes a country selector, and there is no address_country key in either the English or Hebrew block of resources/js/i18n/index.js. AddressController accepts any 2-letter country server-side, but the UI can never send anything but US — odd for a bilingual EN/Hebrew store that implicitly serves Israeli customers whose addresses do not fit US "State"/ZIP conventions. Add a country <select> to both forms and relabel/adapt the state and postal fields per selected country. Feature/component test: submitting an address with country: IL persists and round-trips correctly through the API; existing US-only submissions are unaffected.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern',
                'description' => 'WishlistButton.jsx, StarRating.jsx, and ProductGallery.jsx all mark selectable toggle buttons with aria-pressed, but the color swatches and size buttons on ProductDetail.jsx — the exact same selected/unselected toggle-button pattern — have none, so screen-reader users get no indication of which color or size is currently chosen. Add aria-pressed={color === c} and aria-pressed={size === s} to the respective buttons, consistent with the rest of the app. Component test: a testing-library query asserts the selected swatch/size button has aria-pressed="true" and the others "false", including after switching selection.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Catalog.jsx conflates a failed product fetch with a genuinely empty catalog',
                'description' => 'Catalog.jsx has no .catch on its /api/products request, unlike the home-stats/testimonials calls a few lines below which do catch. On any fetch failure, products stays [] and loading becomes false, so the page renders the "no products match your search" / "catalog empty" EmptyState as if that were genuinely true, silently hiding a real outage from shoppers and from anyone monitoring the storefront. Track a distinct loadError state and render a different, explicit error message (not the no-results EmptyState) when the fetch itself failed. Component test: mock a rejected /api/products call and assert the error EmptyState — not the no-results one — renders.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'change-password and self-service account-deletion endpoints have no rate limiting despite re-checking a password',
                'description' => 'POST /change-password and DELETE /account sit only behind auth:sanctum in routes/api.php, with no throttle:* middleware, unlike login/register/reset-password/checkout which all got dedicated RateLimiter::for(...) definitions in AppServiceProvider. Both AuthController::changePassword and deleteAccount re-verify current_password via Hash::check, which means a hijacked or left-open session (XSS, shared computer, leaked cookie) lets an attacker brute-force the real password with unlimited attempts against either endpoint. Add a RateLimiter::for(\'account-security\', ...) keyed by authenticated user id, mirroring the existing \'reviews\' limiter, and apply it to both routes. Feature test: the 6th (or whatever the chosen cap is) request within a minute from the same authenticated user returns 429, while a different user\'s attempts are unaffected (per-user isolation).',
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason',
                'description' => 'A prior task explicitly added Schema::table(\'orders\', ...)->index(\'user_id\') even though orders.user_id already has a foreignId()->constrained() FK, establishing that FK columns in this schema are not assumed to be indexed for query purposes. addresses.user_id was defined the same way (foreignId()->constrained()) but never got the equivalent follow-up index, despite AddressController::index and every checkout/account-addresses load querying addresses by user_id. Add a migration that adds $table->index(\'user_id\') to the addresses table. Test: a migration/schema test asserting the index exists on addresses.user_id, mirroring how the orders.user_id index would be covered.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'chore',
            ],
            [
                'title' => 'No static analysis/lint tooling configured for either half of the stack',
                'description' => 'composer.json has no PHPStan/Larastan in require-dev, and package.json\'s scripts only has build/dev/test — no lint script, and there is no .eslintrc*/eslint.config.*/.prettierrc* anywhere in the repo. For a codebase this size with dozens of controllers and JSX pages, there is currently no automated way to catch unused vars, obvious type errors, or drifted code style before review. Add larastan/larastan plus a phpstan.neon and a composer lint script, and eslint plus eslint-plugin-react-hooks with an npm run lint script — this is purely local tooling/config (composer.json, package.json, phpstan.neon, eslint config), no .github/workflows/*.yml file is touched, so it does not hit the GitHub App workflows-permission wall that blocked tasks 61/65/67/69. Verification: composer lint and npm run lint both exit 0 against current code (add a baseline/ignore list only if genuinely necessary to get there without unrelated rewrites).',
                'agent_name' => 'Ops Agent',
                'task_type' => 'chore',
            ],
            [
                'title' => 'Uncached /api/home-stats runs 3 unindexed-scale queries on every homepage view',
                'description' => 'HomeStatsController::show executes an Order::count(), a Review::count()+avg(), and a join across orders/addresses with distinct()->count() — with zero caching, on the public homepage\'s hero stats strip loaded by every visitor. ProductController::index/show already wrap equivalent listing queries in CatalogCache::remember(...), but that pattern was never extended to home-stats. Wrap the response in CatalogCache::remember(\'home-stats\', ...) with a short TTL, invalidated the same way the product catalog cache already is (e.g. on new paid orders). Feature test: two consecutive calls to /api/home-stats only hit the database once (assert via a query-count/listen check or a cache-store assertion), and the value reflects a new paid order after the cache invalidates.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'feature',
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
            'ProductDetail.jsx never handles a failed product fetch — infinite skeleton, not an error state',
            'Checkout and account address forms hardcode country: US with no country field ever rendered',
            'Color and size selector buttons on ProductDetail.jsx break the app\'s established aria-pressed toggle pattern',
            'Catalog.jsx conflates a failed product fetch with a genuinely empty catalog',
            'change-password and self-service account-deletion endpoints have no rate limiting despite re-checking a password',
            'addresses.user_id has no explicit index, unlike orders.user_id which got one for the same reason',
            'No static analysis/lint tooling configured for either half of the stack',
            'Uncached /api/home-stats runs 3 unindexed-scale queries on every homepage view',
        ])->delete();
    }
};
