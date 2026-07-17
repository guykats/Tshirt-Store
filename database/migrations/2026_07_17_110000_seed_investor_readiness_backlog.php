<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert(array_merge([
            'commit_sha' => null,
            'blocked_reason' => null,
        ], [
            'title' => 'Investor-readiness research + homepage concept mockup',
            'description' => 'Researched what investors actually screen for on an e-commerce site in the first few seconds (single clear value line, tight visual language, trust signals, mobile-first speed) and produced a full concept mockup for a redesigned homepage: stronger hero with value prop + CTA + trust stats, a "why us" trust strip, a curated collection grid, and a brand-story band — all within the existing parchment/ink/brass palette so it is a real evolution, not a rebrand. This mockup is the reference for the implementation tasks below.',
            'agent_name' => 'Creative Agent',
            'task_type' => 'research',
            'status' => 'done',
            'screenshot_path' => 'task-screenshots/investor-homepage-concept.png',
            'completed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]));

        $backlog = [
            [
                'title' => 'Implement investor-ready homepage redesign',
                'description' => 'Build the approved concept (see the research task above) into the real Catalog page: hero with a one-line value proposition + subhead + primary CTA, a trust strip (authentic symbolism, secure checkout, made to order, worldwide shipping), and a brand-story band. Keep it fast and mobile-first — a 5-second glance should tell a stranger who we serve and what we sell.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Site Design Configuration panel (admin)',
                'description' => 'Add a "Design" section to the admin dashboard so the site\'s look can be tuned without a code deploy: logo, accent/brand color, hero tagline + subheading text, hero image/motif, and the homepage stat numbers (pieces shipped, rating, countries). Store as a single site_settings row exposed via a new admin-only API, read by the frontend on load. This is the concrete "graphic design options" control surface for demos and future rebrands.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Real garment mockup imagery',
                'description' => 'Every "product photo" today is a minimalist SVG line icon (star, menorah, chai) — fine as a design motif, but it reads as a wireframe, not a real product, to anyone unfamiliar with the brand. Produce higher-fidelity flat-lay/on-model style mockup imagery per product so the catalog looks like a real, sellable line when demoed.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Social proof & traction section',
                'description' => 'The concept mockup uses placeholder stats (1,200+ pieces shipped, 4.9/5 rating). Wire a real version: a testimonials block (start with 3-4 seed quotes, admin-editable) and a stats strip driven by real data (completed order count, average rating once reviews exist) instead of hardcoded numbers, so nothing shown to an investor is fabricated.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Design system style guide page',
                'description' => 'Add an internal /dashboard/style-guide reference page documenting the color tokens, type scale, spacing, and component patterns already in resources/css/app.css and DesignArt.jsx. Cheap to build, and it signals (accurately) that the visual language is deliberate and consistent rather than ad hoc — exactly what the research flagged as what investors notice.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Mobile-first performance pass (Lighthouse)',
                'description' => 'Run Lighthouse against the catalog and product pages on mobile viewport, fix the top offenders (image sizing, render-blocking assets, layout shift). Speed is one of the most-cited conversion and credibility factors for e-commerce in 2026 — a slow demo undercuts every other design investment.',
                'agent_name' => 'Ops Agent',
                'task_type' => 'infra',
            ],
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
        DB::table('project_tasks')->where('title', 'Investor-readiness research + homepage concept mockup')->delete();
        DB::table('project_tasks')->where('status', 'todo')->delete();
    }
};
