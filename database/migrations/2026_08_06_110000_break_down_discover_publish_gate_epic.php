<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Discover Tab 2.0: Publish Gate + Smart Graphics Management')->value('id');

        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Backend: split design_suggestions status into pending/kept/published and add a publish() action',
                'description' => "Today DesignSuggestionController::keep() (app/Http/Controllers/Api/DesignSuggestionController.php) does two things in one step: it flips the suggestion to status=kept AND immediately creates a Design row with status=pending_approval, which Dashboard.jsx's Pending Designs queue already surfaces — so a suggestion the owner merely liked in Discover lands on the main approval queue instantly, with no separate checkpoint. Fix: add 'published' as a 4th value on design_suggestions.status (currently enum('pending','kept','discarded'), see database/migrations/2026_08_04_190000_create_design_suggestions_table.php) — SQLite (used in tests/CI) doesn't support ALTER on an existing enum/check constraint the way MySQL does, so write this as a new migration that recreates the column via a temporary column + copy + swap (or Schema::table with a raw CHECK constraint rebuild), matching how other enum-widening migrations in this repo (grep for 'enum(' across database/migrations for a prior example) have handled the SQLite/MySQL split — do not use FIELD() or assume MySQL-only ALTER syntax works. Then split the controller action: keep() should only set status='kept' (no Design row created, no promoted_design_id set) and remain guarded to only fire from status='pending'; add a new publish(DesignSuggestion \$designSuggestion) action, guarded to only fire from status='kept', that does what keep() used to do — create the Design row (status=pending_approval, mockup_url=motif, source_agent=design_suggestion, same fields as today) and set promoted_design_id + status='published' — plus a bulk publishAll() action that publishes every currently-kept suggestion in the latest batch in one call (loop the same logic in a DB transaction, returning the list of newly-created designs) for the 'Publish all kept' bulk button. Add routes for publish/{id} and a bulk publish-all under the existing admin-authenticated /design-suggestions route group in routes/api.php, reusing DesignSuggestionPolicy's existing 'update' authorization (add a dedicated policy method only if 'update' isn't semantically right for publish — check app/Policies/DesignSuggestionPolicy.php first). Update DesignSuggestionResource if it hardcodes the status enum anywhere. Feature tests: keep() on a pending suggestion sets status=kept and creates no Design row; publish() on a kept suggestion creates the Design row (pending_approval) and sets status=published + promoted_design_id; publish() rejects a suggestion that isn't 'kept' (422, same pattern as the existing pending-only guards); publishAll() publishes every kept suggestion in one call and is a no-op (200, empty result) when there are none; all three new/changed actions still require admin auth (403 for guest/non-admin, mirroring the existing keep/discard tests in the Discover-related feature test file).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => $epicId,
                'title' => 'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish',
                'description' => "Once the backend publish-gate split exists (status now pending/kept/published/discarded, plus the new publish and publishAll endpoints — see the companion backend task on this epic), redesign resources/js/pages/Discover.jsx to actually read as a management system rather than a one-shot approval queue. Add a stat/filter bar above the grid showing a count per status (pending/kept/published/discarded) as clickable tiles that filter the visible grid to that status — mirror the Team Management board's clickable stat-tile pattern (grep resources/js/pages for the Team Management tiles component for the exact visual/interaction convention to reuse, don't invent a new one). Replace the capped max-w-5xl 3-column layout with a full-width responsive grid that uses the available screen space on wide viewports. Give each status a distinct card treatment (e.g. a left border or badge color per status) so kept-but-unpublished suggestions stay visually distinguishable instead of disappearing from view the way they do today (today's handleKeep immediately filters the card out of `suggestions` — that has to change: kept suggestions must remain visible in a 'kept, awaiting publish' filter/section instead of vanishing, since publish is now a separate later action). Add a per-card 'Publish' button that appears only on kept cards (calling the new publish endpoint) and a prominent 'Publish all kept' bulk button near the stat bar (calling the new publishAll endpoint, disabled/hidden when the kept count is 0) with a status/aria-live confirmation of how many were published, following the same role=\"status\" aria-live pattern already used for discover_loading/discover_generating. Add real English + Hebrew i18n keys for every new label per CLAUDE.md's bilingual-by-default rule (resources/js/i18n/index.js, real Hebrew translations, not machine-translated) and keep the existing accessibility conventions (DesignArt aria-label/role=img, keyboard-operable buttons with visible focus rings via the existing FOCUS_RING constant). Update resources/js/pages/__tests__/Discover.test.jsx (and add new cases) to cover: the stat bar renders correct per-status counts and filters the grid on click; a kept suggestion stays visible (not removed from the list) after Keep and shows a Publish button instead of Keep/Discard; clicking Publish calls the publish endpoint and moves that card to the published state; 'Publish all kept' calls the bulk endpoint and updates all kept cards to published; the bulk button is disabled/hidden with zero kept suggestions.",
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
            'Backend: split design_suggestions status into pending/kept/published and add a publish() action',
            'Frontend: Discover tab redesign — status filter/stat bar, full-width grid, and bulk publish',
        ])->delete();
    }
};
