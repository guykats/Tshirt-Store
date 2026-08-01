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
                'title' => 'VisionerChatController::store leaves an orphaned unanswered user message when the Anthropic API call fails',
                'description' => "app/Http/Controllers/Api/VisionerChatController.php:32-51 persists the admin's VisionerChatMessage (role=user) at line 32, then calls AnthropicClient::converse() at line 45 with no try/catch. AnthropicClient::send() (app/Services/AnthropicClient.php:85-96) throws a bare RuntimeException on any HTTP failure, timeout, or missing API key, which propagates out of store() as a 500. The frontend's send() (resources/js/pages/VisionerChat.jsx:32-46) only wraps the request in a bare catch {} that sets a generic chat_error string and never touches messages, so the just-sent user message never reappears in state. On the next page load, index() (VisionerChatController.php:17-22) replays that same orphaned user message from the DB with no reply after it, making it look like the Visioner Agent silently ignored the owner. Fix by wrapping the converse() call in try/catch and either deleting the orphaned user message on failure or persisting an error-marker assistant reply so the transcript stays coherent.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "HomeStatsController::show runs four uncached aggregate queries, including a JOIN+DISTINCT+COUNT, on every homepage load",
                'description' => "app/Http/Controllers/Api/HomeStatsController.php:18-44 runs an Order::where(...)->count(), a Review::count(), a Review::avg('rating'), and an Order::where(...)->join('addresses', ...)->distinct()->count('addresses.country') with no caching at all, unlike ProductController::index/show (app/Http/Controllers/Api/ProductController.php:21-45), which wrap every read in CatalogCache::remember() with a 300s TTL. This is a public, unauthenticated endpoint hit on every single Catalog.jsx mount, so as the orders/reviews tables grow this becomes a repeated full aggregation (including a join) on the highest-traffic page in the app, with no cache invalidation concerns to weigh against it since the underlying counts only change on real purchases/reviews. Fix by caching the response the same way CatalogCache does (or a lighter fixed-TTL Cache::remember), invalidated on order/review writes or simply left to expire on a short TTL given how slowly these aggregates actually change.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'quality',
            ],
            [
                'title' => "ProductReviews.jsx's eligibility fetch has no error handling, silently and permanently hiding the review form from a purchaser",
                'description' => "resources/js/components/ProductReviews.jsx:47-52 calls api.get(`/api/products/\${productSlug}/reviews/eligibility`).then(...) with no .catch(). If that request fails (network blip, transient 500, session hiccup), it's an unhandled promise rejection and the eligibility state (initialized null at line 17) stays null forever. Every branch that renders something to a logged-in user in the eligibility area (already_reviewed message, the write-review form, or the 'you haven't purchased this' message) is gated on eligibility being truthy, so a logged-in customer who genuinely purchased the product sees nothing in that section at all -- no form, no message, no retry -- indistinguishable from a page still loading. Fix by adding a .catch() that sets an explicit error state and surfaces a retry affordance, matching the error-handling pattern loadReviews() itself should also have for consistency.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Catalog search does a leading-wildcard LOWER() LIKE scan against unindexed name/description columns on every new search term",
                'description' => "app/Http/Controllers/Api/ProductController.php:28-35 matches search input with whereRaw('LOWER(name) LIKE ?', [...]) / LOWER(description) LIKE ?, both wrapped in leading-and-trailing '%' wildcards -- a pattern that can never use a B-tree index even if one existed, and database/migrations/2026_07_16_100300_create_products_table.php never adds one on name or description (only 2026_07_20_121000_add_indexes_to_products_and_orders_tables.php's status/user_id indexes exist). CatalogCache::remember() (app/Services/CatalogCache.php) only helps a second identical search term within its 300s TTL -- Catalog.jsx's 350ms-debounced search box (resources/js/pages/Catalog.jsx:88-94) means nearly every distinct term a shopper types triggers a fresh full table scan across the whole active catalog. This gets worse linearly as the catalog grows (epics #15/#16 are actively growing it). Fix direction: add a MySQL FULLTEXT index on name/description with a LIKE-based fallback path for SQLite (tests run on SQLite per CLAUDE.md's SQL-portability rule, which has no FULLTEXT support), and switch the query to use it when available.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'quality',
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
            'VisionerChatController::store leaves an orphaned unanswered user message when the Anthropic API call fails',
            'HomeStatsController::show runs four uncached aggregate queries, including a JOIN+DISTINCT+COUNT, on every homepage load',
            "ProductReviews.jsx's eligibility fetch has no error handling, silently and permanently hiding the review form from a purchaser",
            "Catalog search does a leading-wildcard LOWER() LIKE scan against unindexed name/description columns on every new search term",
        ])->delete();
    }
};
