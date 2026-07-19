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
                'title' => 'Product reviews & ratings',
                'description' => 'Let customers who purchased a product leave a 1-5 star rating plus an optional written review, shown on the product page. This is a real prerequisite the "Social proof & traction section" task flagged as missing (its stats strip deliberately omits a rating number because no review data exists yet) — once this ships, the homepage stats strip can be revisited to show a real average rating instead of a qualitative badge.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Wishlist / saved items',
                'description' => 'Let a logged-in customer save products to a personal wishlist from the catalog/product page and view it from their account. Common expected e-commerce feature that is currently missing entirely — no Wishlist model or route exists in the codebase today.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Rate limiting audit for public catalog/search endpoints',
                'description' => 'Auth endpoints (login, register, password reset, visioner-chat) already have throttle middleware via RateLimiter::for in AppServiceProvider, but the public catalog listing, product detail, and search/filter endpoints added since have no throttling at all — they are open to trivial scraping/abuse. Add sensible per-IP rate limits to the public read API surface without breaking normal browsing.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
            ],
            [
                'title' => 'Caching layer for catalog listings',
                'description' => 'Catalog/product listing queries currently hit the database on every request with no caching (the only existing Cache::remember usage in the codebase is for the PayPal access token). Add response/query caching for catalog and product-detail reads with sane invalidation on product/variant writes, to reduce load and improve latency as the catalog grows.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
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
            'Product reviews & ratings',
            'Wishlist / saved items',
            'Rate limiting audit for public catalog/search endpoints',
            'Caching layer for catalog listings',
        ])->delete();
    }
};
