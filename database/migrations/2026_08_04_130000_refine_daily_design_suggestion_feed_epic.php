<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected string $title = 'Daily Design Suggestion Feed';

    protected string $originalDescription = 'A new "Discover" admin tab surfaces 20 fresh t-shirt graphic candidates every morning at 6am (Design has a status enum with an "archived" value that was defined but never implemented - this finally uses it) for the owner to review; anything not kept gets replaced by the next batch. New design_suggestions table (batch_date, motif, style, status, promoted_design_id) kept separate from the existing designs table so a high-volume daily churn does not compound the known bugs already logged against Design (missing archive path, no re-approve/re-reject guard, cache not flushed on decision). Keeping a suggestion promotes it into a real Design row (status=pending_approval), so it flows into the existing approve/reject/product-attachment pipeline unchanged rather than inventing a second parallel workflow. The 6am job reuses the real Laravel scheduler already wired up in bootstrap/app.php (same mechanism as the existing 3am backup job) - no new GitHub Actions workflow needed.';

    protected string $refinedDescription = 'A new "Discover" admin tab surfaces 20 fresh t-shirt graphic candidates every night at 3am (matching the existing backup job\'s time slot; Design has a status enum with an "archived" value that was defined but never implemented - this finally uses it) for the owner to review each morning; anything not kept gets replaced by the next batch. New design_suggestions table (batch_date, motif, style, status, promoted_design_id) kept separate from the existing designs table so a high-volume daily churn does not compound the known bugs already logged against Design (missing archive path, no re-approve/re-reject guard, cache not flushed on decision). Keeping a suggestion promotes it into a real Design row (status=pending_approval), so it flows into the existing approve/reject/product-attachment pipeline unchanged rather than inventing a second parallel workflow. The 3am job reuses the real Laravel scheduler already wired up in bootstrap/app.php as a second scheduled entry alongside the existing backup job - it is entirely independent of the separate pm-agent.yml GitHub Actions automation toggle (a different system: server-side cron vs. GitHub Actions) and keeps running regardless of whether that toggle is enabled or disabled. The Discover tab also gets a "Generate now" button so the owner can trigger an immediate on-demand batch outside the nightly schedule, reusing the exact same generation logic as the scheduled job.';

    public function up(): void
    {
        DB::table('epics')
            ->where('title', $this->title)
            ->update([
                'description' => $this->refinedDescription,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('epics')
            ->where('title', $this->title)
            ->update([
                'description' => $this->originalDescription,
                'updated_at' => now(),
            ]);
    }
};
