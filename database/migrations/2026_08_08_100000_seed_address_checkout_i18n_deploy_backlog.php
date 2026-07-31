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
                'title' => 'Saved-address routes (/api/account/addresses) carry no rate limiting at all',
                'description' => "routes/api.php:104-108 registers GET/POST/PUT/DELETE/default-set on /api/account/addresses inside the auth:sanctum group with no throttle: middleware, unlike every comparable authenticated write surface in the same file (change-password and delete-account use throttle:account-security, reviews use throttle:reviews, checkout uses throttle:checkout). A compromised session or buggy client retry loop can spam unlimited address rows via POST, or hammer PUT/DELETE against sequential address IDs, with zero rate ceiling. Fix by adding a throttle: limiter to this route group, mirroring the pattern already shipped for account-security and reviews.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Checkout.jsx shows a permanently blank page if the product fetch fails',
                'description' => "resources/js/pages/Checkout.jsx's api.get(`/api/products/\${productId}`) call (around line 133-143) has no .catch(), and the component returns null whenever !product (line 158). If the request fails (network blip, 404, 500), product never gets set, so the shopper is stuck on a silent, permanently blank page with no error message, spinner, or retry option — unlike Catalog.jsx, which already has a full accessible error/retry state for the same failure mode. Fix by adding a .catch() that sets an error state and rendering an accessible error message with a retry action, matching Catalog.jsx's existing pattern.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'AccountSettings.jsx swallows a failed address-list fetch as "no saved addresses"',
                'description' => "resources/js/pages/AccountSettings.jsx:49-51 calls api.get('/api/account/addresses').then(...) with no .catch(). On a failed request (network error, 401 session expiry, 500), addresses stays [] and the UI renders the empty-state copy ('you have no saved addresses'), misrepresenting a fetch failure as a genuinely empty address book, plus an unhandled promise rejection logged to the console. Fix by adding a .catch() that sets a distinct error state, rendered separately from the true empty state, matching the same failed-vs-empty distinction already tracked for Wishlist.jsx.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Hebrew-preferring returning visitors get an LTR document on page load until they manually toggle the language switcher',
                'description' => "resources/js/i18n/index.js initializes the active language from localStorage.getItem('locale'), but nothing sets document.documentElement.dir/lang to match on startup. resources/views/app.blade.php:2 only sets lang from the server-side Laravel locale (never dir), and Layout.jsx's toggleLocale() (lines 16-17) is the only place dir gets set, and only in response to an explicit click. A returning visitor with locale=he saved in localStorage renders all-Hebrew text inside an <html dir=\"ltr\"> (or unset) document on every load or refresh — RTL-mirrored layout, form alignment, and icon placement are all wrong until they manually toggle the switcher once. Fix by applying dir/lang to document.documentElement as soon as i18n resolves the stored locale, not only inside toggleLocale.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Deploy pipeline lifts maintenance mode before the new frontend build is uploaded, exposing a backend/frontend mismatch window',
                'description' => ".github/workflows/deploy.yml: the first SSH step puts the site in maintenance mode, deploys the new backend code, and runs migrations, and its `trap 'php artisan up || true' EXIT` brings the site back live at the end of that same step — before the separate \"Upload built frontend assets\" scp step has copied the new public/build/* files, and before the final \"Recache config, routes and views\" step runs. There is a live window where real traffic hits the new backend (new migrations, new Blade/route/view logic) while public/build/* still holds the previous deploy's hashed assets, and a slow or failed scp step can leave that window open indefinitely or leave partially-overwritten hashed asset files serving broken JS/CSS to real visitors. Fix by keeping the site in maintenance mode until after the frontend asset upload and cache-rebuild steps both succeed.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'SyncApprovedTaskTitles/SyncEpicDecisions read PM_AGENT_BOARD_TOKEN via env() directly, so their own tests silently test nothing',
                'description' => "app/Console/Commands/SyncApprovedTaskTitles.php:19 and app/Console/Commands/SyncEpicDecisions.php:19 both read the token with \$token = (string) env('PM_AGENT_BOARD_TOKEN', ''), bypassing the config/services.php wrapper ('services.pm_agent.board_token') that config('services.pm_agent.base_url') on the very next lines already uses correctly. Because .env ships PM_AGENT_BOARD_TOKEN= (an empty string, not absent), Dotenv caches that empty value into \$_ENV/\$_SERVER at boot; when the test suite calls putenv('PM_AGENT_BOARD_TOKEN=board-secret') to simulate a configured token (SyncApprovedTaskTitlesCommandTest and SyncEpicDecisionsCommandTest, several cases each), env()'s underlying repository still resolves the boot-time \$_ENV/\$_SERVER value ahead of the live putenv() override, so the command silently takes its 'token not set' early-return path every time — verified directly in tinker: putenv() is set, then env('PM_AGENT_BOARD_TOKEN','') still returns ''. The result: the four tests asserting real revoke/grant sync behavior (2 in each Command test) already fail on main today (confirmed by reproducing on a clean checkout with no other changes), and the tests that claim to cover 'leaves state untouched when production is unreachable' pass only by coincidence, since the untouched-early-return path is identical whether or not a token is genuinely configured — meaning this critical approval-gate-integrity sync mechanism (see CLAUDE.md's PM_AGENT_BOARD_TOKEN section) currently has no real test coverage of its actual revoke/grant logic at all. Fix by switching both commands to read config('services.pm_agent.board_token') instead of env() directly, and updating both test files to configure the token via config(['services.pm_agent.board_token' => ...]) instead of putenv(), so the config binding (which tests can reliably override) is the single source of truth already established by base_url's existing pattern.",
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
            'Saved-address routes (/api/account/addresses) carry no rate limiting at all',
            'Checkout.jsx shows a permanently blank page if the product fetch fails',
            'AccountSettings.jsx swallows a failed address-list fetch as "no saved addresses"',
            'Hebrew-preferring returning visitors get an LTR document on page load until they manually toggle the language switcher',
            'Deploy pipeline lifts maintenance mode before the new frontend build is uploaded, exposing a backend/frontend mismatch window',
            'SyncApprovedTaskTitles/SyncEpicDecisions read PM_AGENT_BOARD_TOKEN via env() directly, so their own tests silently test nothing',
        ])->delete();
    }
};
