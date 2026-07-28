<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('epics')->insert([
            'title' => 'Discover Tab 2.0: Publish Gate + Smart Graphics Management',
            'description' => "Two real gaps in the just-shipped Daily Design Suggestion Feed (Discover tab), grounded in the actual code: (1) DesignSuggestionController::keep() does two things in one step today - it marks the suggestion 'kept' AND immediately creates a Design row with status=pending_approval, which is exactly what Dashboard.jsx's Pending Designs queue filters on - so a suggestion the owner merely liked in Discover shows up on the main Dashboard's approval queue instantly, with no separate 'ready to publish' checkpoint. (2) Discover.jsx is functionally thin: a capped max-w-5xl 3-column grid, kept/discarded suggestions just vanish client-side with no way to see what was recently kept, no filtering, no batching by motif or status, and a lot of unused horizontal space on wide screens - it doesn't read as a management system, just a one-shot approval queue. Fix: split design_suggestions.status into pending -> kept -> published (add 'published' as a 4th enum value alongside the existing pending/kept/discarded) and split keep() into two actions - keep() only flips status to 'kept' (no Design row yet, stays visible in Discover in a distinct 'kept, awaiting publish' section/filter), and a new publish() action is what actually creates the Design row (status=pending_approval) and only then does it reach Dashboard's Pending Designs queue - the owner's own two-step curation (like, then formally publish) rather than one irreversible click. Pair this with a real Discover redesign: a stat/filter bar (counts per status: pending/kept/published/discarded, clickable like the Team Management board's tiles), full-width responsive grid using the available screen instead of a capped column, distinct status-colored cards so kept-but-unpublished work stays visible instead of disappearing, and a bulk 'Publish all kept' action for when several have piled up. This is a UX and workflow maturity pass on a feature that already works end-to-end, not new infrastructure - low risk, real day-to-day usability payoff for whoever reviews these every morning.",
            'agent_name' => 'Visioner Agent',
            'status' => 'proposed',
            'priority' => 0,
            'decided_by' => null,
            'decided_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('epics')->where('title', 'Discover Tab 2.0: Publish Gate + Smart Graphics Management')->delete();
    }
};
