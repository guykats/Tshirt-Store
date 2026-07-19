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
                'title' => 'XML sitemap generation',
                'description' => 'public/robots.txt exists but nothing references a sitemap, and there is no sitemap.xml route at all — the site has JSON-LD and OG tags per product (see commits for "Product structured data" and "Open Graph / social share meta tags") but no way for search engines to discover all product URLs beyond crawling links. Add a dynamic sitemap route (e.g. GET /sitemap.xml in routes/web.php, rendered as XML from active products plus static routes like /, /about) and add a "Sitemap:" line to public/robots.txt pointing at it. Keep it SQLite/MySQL agnostic and covered by a feature test asserting valid XML and that active (not draft) products appear.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Self-service order cancellation',
                'description' => 'Customers can view order history (see OrderController@index/show and resources/js/pages/Orders.jsx) but have no way to cancel an order themselves — only the admin-side "approve" action exists (OrderController@approve). Add a customer-facing cancel action (e.g. POST /api/orders/{order}/cancel, Sanctum-authenticated, restricted to the order\'s own owner and only while the order is still in a cancellable pre-fulfillment status such as pending/awaiting-approval — not after it has been captured/shipped) with a confirmation step in the UI and a system-event log entry, matching the audit-trail convention used elsewhere (see SystemEvent::log calls in DesignController/OrderController). Write feature tests: owner can cancel a cancellable order, cannot cancel another user\'s order, cannot cancel an already-fulfilled order.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Privacy Policy & Terms of Service pages',
                'description' => 'The site takes real payment via PayPal checkout but has no Privacy Policy or Terms of Service page anywhere (checked resources/js/pages/ and Layout.jsx — no such routes, no footer legal links). Add two bilingual static content pages (/privacy and /terms, following the About.jsx page pattern for layout/styling and useDocumentMeta for titles) covering standard e-commerce disclosures (data collected at checkout, PayPal as payment processor, order/account data retention, contact info) and add footer links to both from resources/js/Layout.jsx. Content should be real, sensible boilerplate appropriate for a small apparel store, not lorem ipsum, in both English and Hebrew.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'content',
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
            'XML sitemap generation',
            'Self-service order cancellation',
            'Privacy Policy & Terms of Service pages',
        ])->delete();
    }
};
