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
                'title' => 'design_suggestions table has no index on batch_date/status despite every Discover-tab query filtering and sorting on them',
                'description' => "database/migrations/2026_08_04_190000_create_design_suggestions_table.php (lines 17-28) creates design_suggestions with only a primary key and a promoted_design_id foreign key — no index on batch_date or status. DesignSuggestionController::index() (app/Http/Controllers/Api/DesignSuggestionController.php:23-27) runs orderByDesc('batch_date')->orderByDesc('id')->paginate(20) on every Discover-tab load, and publishAll() (lines 118-124) runs an unindexed MAX(batch_date) scan followed by a WHERE status = 'kept' AND batch_date = ? scan, on every bulk-publish click. Since the nightly app:generate-design-suggestions cron inserts a fresh batch_size (default 20) rows every single day forever with no pruning, this is a full table scan + filesort that gets slower every day, unlike every other high-traffic table in the app (orders, products, addresses) which already got this exact fix. Fix: add a composite index on (batch_date, status) via a new migration.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "config/design_suggestions.php's motif rotation list was never updated with the 7 new HebrewMark motifs, so the Discover feed can never suggest them",
                'description' => "config/design_suggestions.php:19-29 hardcodes only the original 9 motifs (star-of-david, menorah, chai, shalom, hamsa, pomegranate, aleph, olive-branch, hebrew-script) for DesignSuggestionGenerator to rotate through, even though its own doc comment (lines 10-15) explicitly says it \"mirrors resources/js/components/DesignArt.jsx's REGISTRY keys exactly\" and must be \"kept... in sync if a motif is ever added.\" DesignArt.jsx's REGISTRY (lines 156-177) has since grown to 16 keys — the Catalog Depth epic added emunah, bracha, tikvah, ahava, simcha, emet, and or — but this config file was never updated to match. Every nightly cron run and every admin \"Generate now\" click therefore can only ever suggest the original 9 motifs; the 7 newer ones (already live on real catalog products) are structurally unreachable from the Discover pipeline. This is a distinct file/subsystem from the already-tracked hero_motif Design-Settings-dropdown gap. Fix: add the 7 missing keys to config/design_suggestions.php's motifs array.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "PasswordResetController::reset() never clears is_guest, so a guest who legitimately secures their own account via password reset remains permanently vulnerable to the already-tracked no-proof /register takeover",
                'description' => "AuthController::register() (app/Http/Controllers/Api/AuthController.php:39,45-53) treats any User row with is_guest = true as unclaimed and lets a new registration silently take it over — overwriting its password and logging the caller in — which is already a tracked issue on its own. But PasswordResetController::reset() (app/Http/Controllers/Api/PasswordResetController.php:34-56), the legitimate self-service way a guest-checkout customer would secure their account (using the emailed reset token as real proof of email ownership), only ever does \$user->forceFill(['password' => \$password])->save() — it never sets is_guest = false. The result: even after a guest rightfully claims their account through the proper channel, the row still reads is_guest = true in the database, so it remains eligible for the exact same no-proof /register takeover indefinitely — securing your account the \"right\" way provides no actual protection. Fix: have PasswordResetController::reset()'s closure also set is_guest = false when it was previously true.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
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
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'design_suggestions table has no index on batch_date/status despite every Discover-tab query filtering and sorting on them',
            "config/design_suggestions.php's motif rotation list was never updated with the 7 new HebrewMark motifs, so the Discover feed can never suggest them",
            "PasswordResetController::reset() never clears is_guest, so a guest who legitimately secures their own account via password reset remains permanently vulnerable to the already-tracked no-proof /register takeover",
        ])->delete();
    }
};
