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
                'title' => 'deploy.yml has no rollback path when a mid-deploy step fails, so a bad release can go live instead of the pipeline reverting',
                'description' => ".github/workflows/deploy.yml's first SSH step (lines 37-59) runs `set -e`, enters maintenance mode, then immediately does `git reset --hard origin/main` (line 51) BEFORE `composer install` (52), `migrate --force` (58), and `storage:link` (59) run. The only safety net is `trap 'php artisan up || true' EXIT` (line 48), whose comment (lines 41-46) only promises the site won't hang in maintenance mode forever. If `composer install` hits a transient network error, or a migration fails partway, `set -e` fires the trap immediately — which takes the site OUT of maintenance mode — but the working tree has already been force-reset to the new (possibly half-installed-dependency, half-migrated) commit. Live traffic then hits a broken code/schema combination instead of the pipeline reverting to the last known-good commit. This is distinct from the already-tracked 'maintenance mode lifted before frontend upload' and 'no post-deploy health check' gaps — this is about failure *recovery*, not the happy path or post-deploy verification. Fix: on failure, restore the previous commit (e.g. capture the pre-reset SHA and `git reset --hard` back to it) before lifting maintenance mode, or otherwise leave the site in maintenance mode with a clear signal rather than silently serving a broken release.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "ProductVariantController::store/update crash with a raw 500 on a concurrent duplicate submit, unlike ReviewController's identical race",
                'description' => "app/Http/Controllers/Api/Admin/ProductVariantController.php's store() (lines 16-38) and update() (40-69) both call duplicateCombo() (118-135), a check-then-insert race: it queries for an existing (product_id, size, color) row and returns a clean 422 only if one is already found — otherwise store()/update() proceed straight to create()/update() with no try/catch. product_variants has a real DB-level unique constraint on (product_id, size, color) (database/migrations/2026_07_16_100400_create_product_variants_table.php:21, 'uniq_variant'), so if an admin double-clicks Save, or two admins submit the same combo concurrently, the second request's duplicateCombo() check can pass before the first request's insert commits, and the unique constraint then throws an uncaught QueryException -> raw 500. ReviewController::store() (app/Http/Controllers/Api/ReviewController.php:87-92) guards this exact same check-then-insert race on its own unique constraint with `catch (QueryException \$e) { return response()->json([...], 422); }`, but ProductVariantController never does. Fix: wrap the create()/update() calls in a QueryException catch that returns the same clean 422 duplicateCombo() already returns for the non-race case.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Orders.jsx treats a failed order-history fetch as "you have no orders yet" instead of showing an error',
                'description' => "resources/js/pages/Orders.jsx's initial load (lines 22-26) is `api.get('/api/orders').then((res) => setOrders(res.data.data)).finally(() => setLoading(false))` — no `.catch()` at all. If the request 500s, times out, or the session expires mid-load, `orders` stays its initial `[]` and `loading` flips to `false`, so the component renders the same EmptyState ('orders_empty') a customer with a genuinely empty order history would see (lines 47-54), silently hiding the real error. This is the identical empty-vs-error conflation already fixed for Catalog, ProductDetail, Wishlist, AccountSettings, and Checkout in this codebase, but Orders.jsx itself was never covered — only its separate pagination gap is currently tracked. Fix: add a `.catch()` that sets a distinct error state and renders an error message instead of the empty-state art.",
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
            'deploy.yml has no rollback path when a mid-deploy step fails, so a bad release can go live instead of the pipeline reverting',
            "ProductVariantController::store/update crash with a raw 500 on a concurrent duplicate submit, unlike ReviewController's identical race",
            'Orders.jsx treats a failed order-history fetch as "you have no orders yet" instead of showing an error',
        ])->delete();
    }
};
