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
                'title' => 'VisionerChatController pulls the OLDEST 40 messages as conversation history, not the most recent — the chat silently stops seeing new turns after 40 total',
                'description' => "app/Http/Controllers/Api/VisionerChatController.php:38-43 builds the history sent to Claude with `VisionerChatMessage::query()->orderBy('created_at')->limit(40)->get()` — ascending order then `limit(40)`, i.e. the *earliest* 40 rows ever created in this single global chat thread, not a sliding window of the most recent 40. Failure scenario: once the Visioner chat thread accumulates more than 40 messages (~20 exchanges, easily reached over the project's lifetime since it's one continuous global conversation with no per-session split), every subsequent store() call — including the brand-new user message just inserted a few lines above at line 32-36 — sends Claude the same stale earliest-40-message window forever. The model replies based on a conversation frozen in the past and never sees anything the owner has typed since message #40, producing confused or non-sequitur replies with no error surfaced anywhere in the UI. Fix by ordering `created_at` descending with `limit(40)`, then reversing the collection before mapping to Anthropic's message format.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'DesignSuggestionGenerator has no same-day idempotency check — the nightly cron and an admin "Generate now" click colliding doubles that day\'s batch with no unique constraint to catch it',
                'description' => "app/Services/DesignSuggestionGenerator.php:22-43 (shared by the nightly `app:generate-design-suggestions` schedule and DesignSuggestionController::generateNow) always inserts a fresh `batch_size` (default 20) rows stamped with the target batch date, regardless of whether a batch for that date already exists. `database/migrations/2026_08_04_190000_create_design_suggestions_table.php` has no unique constraint on `batch_date`/`motif` or anything else. Failure scenario: the nightly cron runs its normal batch, and later the same day an admin clicks \"Generate now\" on the Discover tab (or the scheduler fires twice due to any hiccup) — that day's feed silently holds 40 pending suggestions instead of 20, with duplicate motifs, and DesignSuggestionController::publishAll (which operates on `max('batch_date')`) will bulk-publish across the doubled set. Fix by checking for an existing batch on the target date before generating (skip or explicitly replace), rather than always inserting blindly.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'EpicController::approve/reject/delay have no guard on the epic\'s current status, so an already-approved epic with real project_tasks already spawned can be silently reverted to "proposed" via a direct API call',
                'description' => "app/Http/Controllers/Api/EpicController.php:30-106 — approve(), reject(), and delay() each only check `abort_unless(\$request->user()->isAdmin(), 403)` and then unconditionally overwrite `status` (delay forces it back to 'proposed' at line 93) with no precondition that the epic isn't already `approved`/`rejected`. The React UI (resources/js/pages/Epics.jsx) only renders these action buttons for `status === 'proposed'`, so it isn't reachable through normal clicks today, but any direct `POST /api/epics/{id}/delay` (a retried request, a stray script, a future UI bug) on an already-approved epic desyncs the board: `project_tasks.epic_id` rows already spawned under that epic remain in place while the epic itself reads as merely 'proposed' again, violating CLAUDE.md's stated invariant that task breakdown only happens once approved, and `decided_by`/`decided_at` are left stale from the original approval decision. Fix by adding a status precondition (422 if the epic isn't currently in the expected source status) to all three actions.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "HealthController's docstring claims it lets \"the deploy pipeline itself confirm the app is actually serving after a release,\" but its only check is the database — a broken storage:link or queue connection passes silently",
                'description' => "app/Http/Controllers/Api/HealthController.php:23-35 — `show()` builds `\$checks` from a single entry, `checkDatabase()` (line 26), despite the class docstring (lines 12-21) explicitly framing the endpoint as existing so a post-deploy check can catch more than \"a raw HTTP 200 on '/' doesn't prove the DB connection works.\" The real deploy sequence (per CLAUDE.md: composer install, php artisan migrate --force, storage:link) can fail at the storage:link step independently of the database — product/design images would 404 site-wide while this endpoint still reports `{\"status\":\"ok\"}`. This is distinct from the already-tracked \"deploy pipeline never verifies the site actually came back up\" item: it's specifically that even once a post-deploy check is wired into the pipeline, it would still miss a broken storage symlink because the endpoint itself doesn't check for one. Fix by adding a cheap check that `public/storage` resolves to `storage/app/public` (e.g. `is_link` + `readlink` comparison) alongside the existing DB check.",
                'agent_name' => 'Ops Agent',
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
            'VisionerChatController pulls the OLDEST 40 messages as conversation history, not the most recent — the chat silently stops seeing new turns after 40 total',
            'DesignSuggestionGenerator has no same-day idempotency check — the nightly cron and an admin "Generate now" click colliding doubles that day\'s batch with no unique constraint to catch it',
            'EpicController::approve/reject/delay have no guard on the epic\'s current status, so an already-approved epic with real project_tasks already spawned can be silently reverted to "proposed" via a direct API call',
            "HealthController's docstring claims it lets \"the deploy pipeline itself confirm the app is actually serving after a release,\" but its only check is the database — a broken storage:link or queue connection passes silently",
        ])->delete();
    }
};
