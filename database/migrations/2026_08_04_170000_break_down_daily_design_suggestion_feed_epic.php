<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Daily Design Suggestion Feed')->value('id');

        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Backend: nightly design_suggestions generation, promotion pipeline, and on-demand Discover batch endpoints',
                'description' => "The 'Daily Design Suggestion Feed' epic needs a data layer and generation pipeline before any UI can exist. Add a new design_suggestions table (batch_date date, motif string, style string nullable, status enum('pending','kept','discarded') default 'pending', promoted_design_id nullable FK to designs nullOnDelete, timestamps) with a DesignSuggestion model using the #[Fillable([...])] attribute pattern (see app/Models/Design.php). Kept deliberately separate from the existing designs table per the epic so a high-volume nightly churn doesn't compound the already-logged Design bugs (missing archive path, no re-approve/re-reject guard, cache not flushed on decision - see the still-open \"Design records have no delete/archive path\" backlog item). Add a DesignSuggestionGenerator service that produces a batch of 20 candidates by rotating across the 9 existing DesignArt motifs (resources/js/components/DesignArt.jsx's REGISTRY: star-of-david, menorah, chai, shalom, hamsa, pomegranate, aleph, olive-branch, hebrew-script - mirror this exact list server-side as a config array, e.g. config/design_suggestions.php, the same mirrored-list pattern already used for custom_design.php's allowed-motifs on the Custom Design Studio epic), storing motif directly as the suggestion's `motif` (matching how Design::mockup_url already stores a raw motif key, not an image URL) and leaving `style` null for now (that column is prepared for the separate, still-proposed 'Style-Variant Design System Expansion' epic, which has not been approved and must not be implemented as part of this task). Add app/Console/Commands/GenerateDesignSuggestions.php and wire it into bootstrap/app.php's scheduler as a second entry alongside the existing 3am backup job (the \$schedule->command('app:backup-database')->dailyAt('03:00') line) - this is server-side cron, entirely independent of and unaffected by the separate pm-agent.yml GitHub Actions automation toggle. Add an admin-only DesignSuggestionController with index (paginated, latest batch_date first), generateNow (invokes the exact same generator service the scheduled command uses, for a 'Generate now' button), keep (promotes the suggestion into a real Design row with status=pending_approval and mockup_url set to the suggestion's motif, then sets the suggestion's promoted_design_id and status=kept, so it flows into the existing DesignController approve/reject/product-attachment pipeline unchanged rather than inventing a second parallel workflow), and discard (status=discarded) actions, plus routes under the existing admin-authenticated group in routes/api.php (mirroring the /designs routes' auth/authorize pattern) and a DesignSuggestionPolicy modeled on DesignPolicy. Feature tests: the generator produces exactly 20 suggestions for a batch; keep() creates a Design row with status=pending_approval, links promoted_design_id, and sets the suggestion to status=kept; the scheduled command is registered on the schedule and callable directly; generateNow and the index/keep/discard endpoints all require admin auth (403 for a non-admin or guest).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => $epicId,
                'title' => "Frontend: 'Discover' admin tab surfacing the nightly design suggestion batch",
                'description' => "Once the backend design_suggestions batch/keep/discard/generateNow endpoints exist (see the companion backend task on this epic), build the actual 'Discover' tab the owner reviews every morning. Add a new admin-only tab/route (e.g. resources/js/pages/Discover.jsx, linked from the same admin nav used by the existing Designs/Products/Dashboard tabs) that fetches the latest batch of up to 20 suggestions and renders each as a card using <DesignArt motif={suggestion.motif} .../> for the live preview (the same component/prop pattern already used throughout the app - see Dashboard.jsx's pending-designs review list for the closest existing analog), with a 'Keep' and 'Discard' action per card (calling the backend's keep/discard endpoints and removing the card from the grid on success) and a prominent 'Generate now' button at the top of the tab that calls generateNow and refreshes the grid - anything not kept before the next nightly batch (or an on-demand one) gets replaced, so make that replacement behavior clear in the UI copy near the button. Add real English + Hebrew i18n keys for every new label (per CLAUDE.md's bilingual-by-default rule, resources/js/i18n/index.js - real Hebrew translations, not literal/machine-translated ones) and follow the app's existing accessibility conventions (aria-label/role=\"img\" on the DesignArt previews per its own `label` prop, a role=\"status\"/aria-live region announcing the 'Generate now' loading/refresh state, keyboard-operable Keep/Discard buttons with clear focus states). Component test: rendering the tab with a mocked batch response shows the expected number of cards; clicking Keep/Discard calls the expected endpoint and removes that card from the grid; clicking Generate now calls the generate endpoint and re-renders the returned batch.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Backend: nightly design_suggestions generation, promotion pipeline, and on-demand Discover batch endpoints',
            "Frontend: 'Discover' admin tab surfacing the nightly design suggestion batch",
        ])->delete();
    }
};
