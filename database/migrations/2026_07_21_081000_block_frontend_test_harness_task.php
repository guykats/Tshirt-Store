<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend automated test harness (Vitest + React Testing Library)')
            ->update([
                'status' => 'blocked',
                'commit_sha' => '900319b6709ab13c4e69aa5fd896ebdc8e36a342',
                'blocked_reason' => 'The harness itself is fully done, verified, and pushed at commit '
                    .'900319b6709ab13c4e69aa5fd896ebdc8e36a342: vitest, @testing-library/react, '
                    .'@testing-library/jest-dom, @testing-library/user-event, and jsdom as devDependencies; a '
                    .'"test": "vitest run" script in package.json; a dedicated vitest.config.js (kept separate '
                    .'from vite.config.js, whose laravel-vite-plugin/Tailwind plugins have no place in a jsdom '
                    .'unit-test run); resources/js/testSetup.js wiring jest-dom matchers + cleanup(); and 7 '
                    .'passing tests across 3 suites, all mocking resources/js/lib/api.js via vi.mock rather than '
                    .'hitting a server - Layout.jsx toggleLocale (resources/js/__tests__/Layout.test.jsx: swaps '
                    .'i18n language, localStorage, and document.dir/lang both en->he and he->en), Faq.jsx '
                    .'(resources/js/pages/__tests__/Faq.test.jsx: accordion expand/collapse, and the size-guide '
                    .'link only appears for the sizing category), and ProductDetail.jsx '
                    .'(resources/js/pages/__tests__/ProductDetail.test.jsx: the size selector disables a real '
                    .'zero-stock variant and a size/color combo that never existed as a variant, and the buy '
                    .'button/link is only enabled once a fully valid, in-stock-eligible variant is selected). '
                    .'Full verification bar passed: npm test (7/7), npm run build, migrate:fresh --seed --force, '
                    .'php artisan test (194/194). Only the CI-wiring half is blocked: adding a "Run frontend '
                    .'tests" step (npm test) to .github/workflows/tests.yml, right after "Install JS dependencies" '
                    .'and before "Build frontend assets"/the PHP test step, was committed locally and pushed, but '
                    .'GitHub rejected it with "refusing to allow a GitHub App to create or update workflow '
                    .'.github/workflows/tests.yml without workflows permission" - the same hard platform-level '
                    .'GitHub App Workflows:write restriction that blocked tasks 61 ("Merge pending Dependabot '
                    .'dependency PRs"), 65 ("Automated production database backups"), and 69 ("CI dependency '
                    .'vulnerability scanning"). That commit was reset out locally (never landed on main) so main '
                    .'stays at the clean, fully-verified 900319b6709ab13c4e69aa5fd896ebdc8e36a342. To unblock: a '
                    .'maintainer with the `workflows` permission (or a human pushing directly, or granting the '
                    .'App installation Workflows: write) needs to apply this 3-line diff to '
                    .'.github/workflows/tests.yml themselves - insert right after the existing '
                    .'"- name: Install JS dependencies\n        run: npm ci" step and before '
                    .'"- name: Build frontend assets":'."\n\n"
                    ."      - name: Run frontend tests\n        run: npm test\n\n"
                    .'(i.e. a new step named "Run frontend tests" running `npm test`, placed between "Install JS '
                    .'dependencies" and "Build frontend assets"). No other change is needed - package.json already '
                    .'has the "test" script and all devDependencies are already in package-lock.json.',
                'completed_at' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')
            ->where('title', 'Frontend automated test harness (Vitest + React Testing Library)')
            ->update([
                'status' => 'in_progress',
                'commit_sha' => null,
                'blocked_reason' => null,
                'updated_at' => now(),
            ]);
    }
};
