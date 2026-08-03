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
                'title' => 'Real product photos have no broken-image fallback, unlike the SVG art they sit alongside',
                'description' => "resources/js/components/GarmentMockup.jsx:138 renders a photographic `motif` as `<img src={motif} alt={label || product?.name || ''} className=\"h-full w-full object-cover\" />` with no `onError` handler. `database/migrations/2026_08_07_160000_seed_photographic_product_images.php` seeded real catalog photos as bare hotlinks to `https://upload.wikimedia.org/wikipedia/commons/...` -- a third-party CDN outside this app's control. If Wikimedia renames, removes, or hotlink-blocks any of those assets, shoppers see a bare broken-image icon on the catalog card and product hero, with no fallback to the SVG DesignArt/GarmentMockup illustration that's otherwise the default rendering path for every non-photographic product (see `isPhotographicMotif`, GarmentMockup.jsx:123-124, which is the only branch point between the two rendering paths). Fix by adding an `onError` handler on that `<img>` that swaps to the existing SVG DesignArt rendering (the same branch used when `motif` isn't a photographic URL) instead of leaving a broken image.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Product image alt_text has no Hebrew field, unlike every other admin-authored public string in the schema',
                'description' => "`app/Http/Controllers/Api/Admin/ProductImageController.php:117` validates `alt_text` as a single free-text column (`'alt_text' => ['nullable', 'string', 'max:255']`, schema in `database/migrations/2026_07_21_111000_create_product_images_table.php:19`), consumed as-is by `resources/js/components/ProductGallery.jsx:46` and `:67` for the gallery's accessible label. Every other piece of admin-authored public copy in this bilingual store is split into explicit `_en`/`_he` pairs -- `Testimonial::author_context_en`/`author_context_he` and `quote_en`/`quote_he` (`app/Models/Testimonial.php:9-10`), `SiteSetting::hero_tagline_en`/`hero_tagline_he` (`app/Models/SiteSetting.php:10`). `alt_text` breaks that established convention: a Hebrew-locale screen-reader user gets English alt text (e.g. \"Plain black crew-neck cotton T-shirt, folded flat against a white background.\" from the seeded photos) on every real product photo, with no way for an admin to supply a Hebrew equivalent anywhere in the schema or UI. Fix by splitting into `alt_text_en`/`alt_text_he` columns and rendering by the active locale, matching the Testimonial/SiteSetting pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "SecurityHeaders' CSP img-src is 'self' data: https:, a wildcard that lets any HTTPS host serve tracking pixels via admin-controlled image fields",
                'description' => "`app/Http/Middleware/SecurityHeaders.php`'s `contentSecurityPolicy()` sets `\"img-src 'self' data: https:\"` (line 99) -- a blanket allowlist covering literally any HTTPS origin. Every other directive in the same policy is scoped tightly to exact trusted hosts: `script-src 'self' ` . PAYPAL_SCRIPT_HOSTS (line 91), `connect-src 'self' ` . PAYPAL_API_HOSTS (line 101), `frame-src ` . PAYPAL_FRAME_HOSTS (line 102). `img-src` is the one exception, and `product_images.url` / `alt_text` (validated only as `['string','max:500']` in `ProductImageController`) plus `SiteSetting.logo_path` both accept arbitrary external hosts with no allowlist. Combined, any admin-account compromise (or a future untrusted-input path into these fields) can silently beacon page views to an attacker-controlled domain via a 1x1 tracking image, with CSP offering no protection since `https:` permits it. Fix by allowlisting the specific trusted image hosts actually in use (e.g. `upload.wikimedia.org`, the app's own storage domain) instead of a blanket `https:`.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'SyncApprovedTaskTitles and SyncEpicDecisions no longer actually sync on a clean checkout — 4 tests fail reproducibly, independent of any other change',
                'description' => "Running the full suite on a clean `migrate:fresh --seed` checkout (no code changes) reproducibly fails 4 tests: `SyncApprovedTaskTitlesCommandTest::test_revokes_a_migration_only_approval_the_live_titles_no_longer_include` and `::test_grants_a_live_approval_the_migration_history_never_recorded` (both assert on `ProjectTask::approved_for_dev` after `php artisan pm-agent:sync-approved-titles`, both get the pre-sync value back untouched), plus `SyncEpicDecisionsCommandTest::test_updates_status_and_priority_of_an_existing_epic_to_match_a_live_decision` and `::test_inserts_a_live_only_epic_that_was_never_migration_seeded` (same untouched-after-sync pattern on `epics`). This was discovered incidentally while verifying an unrelated backlog-seeding migration and confirmed unrelated to it by re-running with that migration removed -- same 4 failures either way. These two commands (`app/Console/Commands/SyncApprovedTaskTitles.php`, `app/Console/Commands/SyncEpicDecisions.php`) are exactly the ones `pm-agent.yml`'s \"Sync real approved-task titles from production\" / \"Sync real epic decisions from production\" steps depend on (per CLAUDE.md's `PM_AGENT_BOARD_TOKEN` section) to reconcile the CI job's replayed local SQLite against real live dashboard approvals before every autonomous run decides what to build -- if this regression is live in production too (not just these tests), the PM Agent may currently be deciding what to build off stale/replayed approval state instead of the real one. `config('services.pm_agent.base_url')` already defaults correctly to `https://store.guykats.com` (config/services.php:74), which rules out a URL-mismatch cause -- both failing tests only diverge from passing tests in this file by requiring the sync's DB-update logic (SyncApprovedTaskTitles.php:49-52 / the equivalent in SyncEpicDecisions.php) to actually execute rather than hitting one of the earlier early-return branches (no token / unreachable production), so start by instrumenting whether `env('PM_AGENT_BOARD_TOKEN', '')` is actually observing the tests' `putenv('PM_AGENT_BOARD_TOKEN=board-secret')` calls in this Laravel/PHPUnit version, since the token-present request-succeeds path is the one path these two tests exercise that the other (passing) tests in the same file don't.",
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
            'Real product photos have no broken-image fallback, unlike the SVG art they sit alongside',
            'Product image alt_text has no Hebrew field, unlike every other admin-authored public string in the schema',
            "SecurityHeaders' CSP img-src is 'self' data: https:, a wildcard that lets any HTTPS host serve tracking pixels via admin-controlled image fields",
            'SyncApprovedTaskTitles and SyncEpicDecisions no longer actually sync on a clean checkout — 4 tests fail reproducibly, independent of any other change',
        ])->delete();
    }
};
