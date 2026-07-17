<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Per user request: the board should always show a live backlog, not just one task
     * at a time created right before it starts. The PM (Orchestrator) seeds several
     * planned tasks across agents up front; work picks them up one at a time (todo ->
     * in_progress -> done, each with a real commit), and the backlog gets topped up
     * again as it runs low, so there's continuous, visible pipeline rather than a
     * single breadcrumb trail.
     */
    public function up(): void
    {
        $now = now();

        $backlog = [
            ['title' => 'Customer order history page', 'description' => 'Customers have no way to see their past orders in the UI, even though GET /api/orders already supports it for non-admins. Add a /orders page.', 'agent_name' => 'Dev Agent', 'task_type' => 'feature'],
            ['title' => 'Wire up catalog pagination controls', 'description' => 'The API paginates products (20/page) but Catalog.jsx only reads res.data.data and ignores the pagination meta, so past 20 products nothing further is reachable.', 'agent_name' => 'Dev Agent', 'task_type' => 'bugfix'],
            ['title' => 'Open Graph / social share meta tags', 'description' => 'Links shared on social media / messaging apps show no preview card (no og:title, og:image, etc). Add them, at least on the catalog and product-detail pages.', 'agent_name' => 'Creative Agent', 'task_type' => 'feature'],
            ['title' => 'Loading skeleton states', 'description' => 'Catalog and ProductDetail currently show a bare "…" while loading instead of a proper skeleton placeholder.', 'agent_name' => 'Creative Agent', 'task_type' => 'design'],
            ['title' => 'Accessibility pass', 'description' => 'Audit alt text, ARIA labels, focus states, and color contrast across the main customer-facing pages.', 'agent_name' => 'Dev Agent', 'task_type' => 'quality'],
            ['title' => 'Brand story / About page', 'description' => 'The collection has a clear philosophy in the hero copy but nowhere it\'s expanded on. A short About page.', 'agent_name' => 'Trend Agent', 'task_type' => 'design'],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
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
        DB::table('project_tasks')->where('status', 'todo')->delete();
    }
};
