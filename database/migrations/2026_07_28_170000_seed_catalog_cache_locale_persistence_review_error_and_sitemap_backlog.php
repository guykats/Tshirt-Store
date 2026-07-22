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
                'title' => 'Catalog cache is invalidated globally by every stock change, defeating the caching layer under real order volume',
                'description' => "app/Services/CatalogCache.php namespaces every cached catalog entry behind a single global 'catalog:cache-version' counter (no per-product cache tags, since the default 'database' cache store doesn't support them) - CatalogCache::flush() (:25-29) just increments that one shared version. ProductVariant::booted() calls CatalogCache::flush() on every created/updated/deleted event (app/Models/ProductVariant.php:16-18), and stock_quantity is mutated on every checkout (CheckoutController.php:153, \$locked->decrement('stock_quantity', ...)) and every cancel/refund/expiry (OrderStockService.php:47, \$variant->increment('stock_quantity', ...)) - none of these are scoped to just the one product/variant that changed. Failure scenario: the moment any customer anywhere places, cancels, or has an order expire, every previously cached /api/products listing and every cached /api/products/{slug} detail page for the entire catalog is invalidated at once, forcing a full re-query+re-serialize on the next request for any product. With even light order traffic within a 5-minute TTL window, the catalog-listing cache built in the 'Caching layer for catalog listings' task effectively never serves a hit in production - it's inert exactly when it matters. Fix: scope invalidation to the affected product (e.g. a cache-version keyed per product_id, or invalidate only that product's show:{id} entry plus the listing cache) instead of one version key shared by every product.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "A customer's chosen site language never persists to their account, so every transactional email ships in English regardless of the Hebrew UI they used",
                'description' => "users.preferred_locale (enum('en','he')->default('en')) is the single source of truth every transactional email/notification keys off: Mail::to(\$order->user)->locale(\$order->user->preferred_locale ?? 'en') in PayPalWebhookController.php:76, OrderController.php:185/187/293, CheckoutController.php:288, plus invoice rendering in InvoiceService.php:18 and password-reset in User.php:97. But the site's actual language toggle (resources/js/Layout.jsx:12-15, toggleLocale()) only ever writes to localStorage.setItem('locale', next), read purely client-side by i18next (resources/js/i18n/index.js). There is no API call anywhere in the frontend (Register.jsx, AccountSettings.jsx, Checkout.jsx all checked) and no backend endpoint that ever writes preferred_locale for a user - UserResource.php:18 only ever reads it out. Failure scenario: a Hebrew-speaking customer switches the whole site to Hebrew, registers, and completes a purchase entirely in Hebrew - their order confirmation, shipped/delivered emails, refund email, PDF invoice, and password-reset email all render in English forever, because preferred_locale stays at its DB default of 'en' with no code path that ever updates it. Fix: send the current i18next language on register/login, or a lightweight PATCH to the user profile whenever the toggle fires, so preferred_locale actually tracks what the customer sees.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'ProductReviews.jsx has no error handling on its review-list or eligibility fetches, so a failed request looks identical to "no reviews yet"',
                'description' => "resources/js/components/ProductReviews.jsx:32-52 - loadReviews() (:32-37) and the eligibility useEffect (:44-52, api.get(\`/api/products/\${productSlug}/reviews/eligibility\`)) both call api.get(...) with no .catch(). On the mount effect (:39-42), a failed loadReviews() call rejects, setLoading(false) still runs via .finally(), but reviews/meta stay at their initial empty defaults (meta.count === 0). Failure scenario: if GET /api/products/{slug}/reviews 500s or times out (flaky network, backend hiccup), the product page silently renders the \"no reviews yet\" empty state - visually indistinguishable from a genuinely unreviewed product - with the error swallowed as an unhandled promise rejection instead of surfacing any error state to the shopper. This is the identical silent-failure pattern already fixed for ProductDetail.jsx's and Catalog.jsx's product fetches and for Wishlist.jsx/account-addresses, but was missed on this component/these two endpoints. Fix: add .catch() to both requests and a retry-capable error state matching the established pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'XML sitemap omits every static content page except the homepage and About, despite each having real per-page SEO meta',
                'description' => "app/Http/Controllers/SitemapController.php:18-29 hard-codes the sitemap's \$urls array to just '/' and '/about' plus one entry per active product; it never includes /faq, /size-guide, /privacy, or /terms, even though all four are real routed pages (resources/js/App.jsx:39-42,91-94 - Privacy, Terms, Faq, SizeGuide) that already received dedicated SEO meta titles/descriptions under the 'Per-page SEO meta titles + descriptions' task. Failure scenario: a search engine crawling sitemap.xml - the primary discovery mechanism the 'XML sitemap generation' task was built to provide - never learns these pages exist except via on-site link crawling, so FAQ and Size Guide content (exactly the kind of long-tail-query pages worth indexing for an apparel store) get no sitemap-driven crawl priority or freshness signal at all. Fix: add static entries for /faq, /size-guide, /privacy, and /terms to the \$urls array alongside / and /about.",
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
        DB::table('project_tasks')->whereIn('title', [
            'Catalog cache is invalidated globally by every stock change, defeating the caching layer under real order volume',
            "A customer's chosen site language never persists to their account, so every transactional email ships in English regardless of the Hebrew UI they used",
            'ProductReviews.jsx has no error handling on its review-list or eligibility fetches, so a failed request looks identical to "no reviews yet"',
            'XML sitemap omits every static content page except the homepage and About, despite each having real per-page SEO meta',
        ])->delete();
    }
};
