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
                'title' => "Homepage's \"countries served\" stat is structurally incapable of ever showing more than 1",
                'description' => "HomeStatsController::show() (app/Http/Controllers/Api/HomeStatsController.php:31-34) computes \$countriesServed = Order::where('payment_status', 'paid')->join('addresses', ...)->distinct()->count('addresses.country'). But every address-creation path in the app hardcodes the country: Checkout.jsx:110's initial form state and AccountSettings.jsx:9/66 both literal/fallback to 'US', and no form anywhere renders a country field to change it (the missing-field UX gap is already tracked separately). Since every addresses.country row in the system is therefore always 'US', this real-time social-proof stat is permanently stuck at 1 no matter how many customers or orders accumulate -- a data-integrity bug distinct from the UX gap, since even after the UX gap is fixed for new addresses, this stat still undercounts every historical order placed before that fix shipped. Fix: either seed/normalize existing addresses once a real country field ships, or exclude this stat from the homepage until country data is actually meaningful.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Deploy pipeline takes the site out of maintenance mode before new frontend assets are uploaded or caches are rebuilt',
                'description' => ".github/workflows/deploy.yml's maintenance-mode trap (line 48, `trap 'php artisan up || true' EXIT`) is scoped to the first SSH step only (\"Pull latest code, install PHP dependencies, and migrate\") -- it fires and brings the site back up as soon as that step's remote script exits, which is before the next two steps run: \"Upload built frontend assets\" (the scp-action step, lines 61-70, which pushes the new public/build/* -- a directory .gitignore excludes from the git reset so it never gets the new bundle any other way) and \"Recache config, routes and views\" (lines 72-84). This means real traffic can hit the live site running the brand-new backend code (already git-reset + migrated) against a stale or momentarily-incomplete frontend bundle and an uncached config, for however long the SCP transfer + recache steps take -- the opposite of what wrapping the risky steps in maintenance mode was meant to prevent. Fix: extend the maintenance window (or add a second down/up bracket) to cover the asset upload and recache steps too.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'EpicController::index has no pagination or row limit at all, unlike its sibling ProjectTaskController',
                'description' => "EpicController::index() (app/Http/Controllers/Api/EpicController.php:13-27) builds its query with ->withCount('tasks')->with('decidedBy')->when(...)->orderByRaw(...)->get() -- a bare ->get() with no ->paginate()/->limit() anywhere, returning every epic row ever created in a single response every time /dashboard/progress loads. This is the opposite failure mode of the already-tracked 'ProjectTaskController::index hard-caps at 200 rows' ticket (that one under-caps; this one has zero cap), and it's a real, designed-in growth risk here specifically: CLAUDE.md's standing operating agreement has the Visioner Agent and the 30-minute autonomous PM cron continuously seed new epics whenever the proposed queue runs thin, and rejected/delayed epics are never deleted, so this table is built to grow indefinitely and this endpoint's response size grows with it. Fix: paginate like ProjectTaskController does, or at minimum cap the result set.",
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
            "Homepage's \"countries served\" stat is structurally incapable of ever showing more than 1",
            'Deploy pipeline takes the site out of maintenance mode before new frontend assets are uploaded or caches are rebuilt',
            'EpicController::index has no pagination or row limit at all, unlike its sibling ProjectTaskController',
        ])->delete();
    }
};
