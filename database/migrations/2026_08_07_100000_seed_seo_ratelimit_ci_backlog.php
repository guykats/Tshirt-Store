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
                'title' => 'The static SEO description meta tag never reflects a product\'s real description, undermining the exact SEO SpaController was built for',
                'description' => "resources/views/app.blade.php:6 hardcodes <meta name=\"description\" content=\"Minimalist streetwear carrying quiet Jewish cultural signal...\"> unconditionally, even though the same file already computes a per-request \$og array (from App\\Http\\Controllers\\SpaController::index) carrying the real product description for /products/{slug} URLs, correctly used for og:description/twitter:description a few lines below. Any crawler or SEO tool that reads the plain SEO description tag (as opposed to Open Graph tags) gets the generic homepage blurb on every product page, never the product's actual description — the entire reason SpaController renders \$og server-side per its own comment. Fix by interpolating \$og['description'] into the <meta name=\"description\"> tag the same way it's used for the OG/Twitter tags below it.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "ProductManagement.jsx's design picker silently caps at the first 20 approved designs, hiding newer ones as the catalog grows",
                'description' => "resources/js/pages/ProductManagement.jsx:78-79 fetches /api/designs?status=approved and assigns the raw response straight into the product create/edit form's design dropdown, but DesignController::index (app/Http/Controllers/Api/DesignController.php:22) paginates the result at 20 with no page param ever requested. Once more than 20 designs have been approved, every design past the first page becomes permanently unselectable from the product form with no indication to the admin that the list is incomplete. Fix by either requesting all approved designs with a high per-page limit, or making the dropdown paginated/searchable.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Admin product, variant, image, and coupon CRUD routes carry no rate limiting at all',
                'description' => "routes/api.php's admin prefix group (around lines 170-205) — POST/PUT/DELETE on /api/admin/products, /api/admin/products/{product}/variants, /api/admin/products/{product}/images (including reorder), and /api/admin/coupons — carries zero throttle: middleware, unlike the pattern already applied consistently to every other authenticated write surface (reviews, wishlist, addresses, checkout capture, testimonials via throttle:reviews/account-security/checkout etc.). A compromised admin session, leaked Sanctum cookie, or buggy client-side retry loop could mass-create/edit/delete products, variants, images, and coupons with no rate ceiling. Fix by adding a throttle:admin-write-style limiter to this route group, matching the fix already shipped for testimonials/addresses.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'GitHubActionsClient, PayPalClient, and AnthropicClient HTTP calls have no timeout, unlike the CI curl step whose missing timeout was already fixed',
                'description' => "app/Services/GitHubActionsClient.php builds every GitHub API request via Http::withToken(...) with no ->timeout() call anywhere (grep -rn \"->timeout(\" app/Services matches nothing across all three services). GitHubActionsClient::enableIfDisabled() runs inline inside EpicController::approve() and ProjectTaskController::approve() on every single approval click, and PmAgentAutomationController::toggle() calls it directly from a web request. If GitHub's API hangs or is unreachable, that PHP-FPM worker blocks indefinitely — the same class of bug already fixed for the CI-side curl step in task 'Fix real crash: disable-if-idle curl step had no timeout', never applied to the production PHP clients. Fix by adding ->timeout()/->connectTimeout() to all three HTTP-calling services.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "tests.yml's PHP version matrix runs with fail-fast: true, defeating the point of testing 3 PHP versions",
                'description' => ".github/workflows/tests.yml declares strategy: fail-fast: true with matrix: php: [8.3, 8.4, 8.5]. With fail-fast enabled, the moment the 8.3 job fails, GitHub Actions cancels the still-running 8.4 and 8.5 jobs before they finish, so a single push can only ever surface one broken PHP version at a time, requiring multiple push-fix-push cycles to discover compatibility issues across all three versions — the entire reason a multi-version matrix exists. Fix by setting fail-fast: false so every PHP version's results are visible in one CI run.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Design-suggestion admin routes (generate/keep/discard/publish/publish-all) carry no rate limiting',
                'description' => "routes/api.php:136-140 registers POST /api/design-suggestions/generate, /keep, /discard, /publish, and /publish-all (all admin-only) with no throttle: middleware, unlike the app's now-consistent practice of throttling comparable admin write surfaces. generateNow in particular (app/Http/Controllers/Api/DesignSuggestionController.php) inserts a full batch (config('design_suggestions.batch_size'), default 20) of new design_suggestions rows on every call with no dedup or cooldown, so repeated clicks or scripted calls can flood the table with junk batches for the same day. Fix by adding a throttle: limiter to this route group, mirroring the fix already shipped for testimonial admin routes.",
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
            'The static SEO description meta tag never reflects a product\'s real description, undermining the exact SEO SpaController was built for',
            "ProductManagement.jsx's design picker silently caps at the first 20 approved designs, hiding newer ones as the catalog grows",
            'Admin product, variant, image, and coupon CRUD routes carry no rate limiting at all',
            'GitHubActionsClient, PayPalClient, and AnthropicClient HTTP calls have no timeout, unlike the CI curl step whose missing timeout was already fixed',
            "tests.yml's PHP version matrix runs with fail-fast: true, defeating the point of testing 3 PHP versions",
            'Design-suggestion admin routes (generate/keep/discard/publish/publish-all) carry no rate limiting',
        ])->delete();
    }
};
