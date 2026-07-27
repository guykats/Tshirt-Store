<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('epics')->insert([
            [
                'title' => 'Daily Design Suggestion Feed',
                'description' => 'A new "Discover" admin tab surfaces 20 fresh t-shirt graphic candidates every morning at 6am (Design has a status enum with an "archived" value that was defined but never implemented - this finally uses it) for the owner to review; anything not kept gets replaced by the next batch. New design_suggestions table (batch_date, motif, style, status, promoted_design_id) kept separate from the existing designs table so a high-volume daily churn does not compound the known bugs already logged against Design (missing archive path, no re-approve/re-reject guard, cache not flushed on decision). Keeping a suggestion promotes it into a real Design row (status=pending_approval), so it flows into the existing approve/reject/product-attachment pipeline unchanged rather than inventing a second parallel workflow. The 6am job reuses the real Laravel scheduler already wired up in bootstrap/app.php (same mechanism as the existing 3am backup job) - no new GitHub Actions workflow needed.',
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Style-Variant Design System Expansion',
                'description' => 'The content behind the Daily Design Suggestion Feed epic: today artwork is 9 hand-authored SVG React components in DesignArt.jsx (star-of-david, menorah, chai, hamsa, etc.) with no style variation and no image-generation pipeline exists anywhere in the codebase. Rather than adding a costly, unbudgeted AI image-generation API, the Creative Agent researches real 2026 apparel design trends (Cultural Roots & Local Identity - heritage symbols, traditional patterns, storytelling - alongside minimalist line art and bold typography are named trends per Printful/Vexels/Kittl 2026 trend reports) and implements roughly 8 named style renderers (minimalist line art, vintage badge/stamp, papercut silhouette, geometric mosaic, hand-lettered typography, illuminated-manuscript ornamental, watercolor wash, modern flat icon) in the existing component system. Combined with the 9 existing motifs that is 70+ combinations - enough for real daily variety for months before repeats, at zero marginal per-image cost and no new external dependency or credential.',
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('epics')->whereIn('title', [
            'Daily Design Suggestion Feed',
            'Style-Variant Design System Expansion',
        ])->delete();
    }
};
