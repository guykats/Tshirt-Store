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
                'title' => 'Product catalog has no bilingual name/description fields, so every product name and description ships in English only regardless of site language',
                'description' => "database/migrations/2026_07_16_100300_create_products_table.php:14,16 defines name and description as single columns, unlike testimonials (author_context_en/he, quote_en/he) or site_settings' hero_tagline_en/he. ProductController::index/show (app/Http/Controllers/Api/ProductController.php:22-34) only ever reads/searches the single name/description columns, so a Hebrew-preferring shopper browsing the fully-RTL, fully-translated storefront still sees every product's actual name and description in raw English -- the one piece of real customer-facing catalog content never made bilingual at the data-model level. Fix by adding name_en/name_he and description_en/description_he columns (backfilling existing rows from the current single column into _en, leaving _he for an admin to fill in), updating ProductController and the admin product form to read/write the locale-appropriate pair, and switching the public search to match against both language columns.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "No in-app way to revoke or demote an admin account -- the only path to creating one is a console command, and there's no deactivate/role-change endpoint anywhere",
                'description' => "app/Console/Commands/CreateAdmin.php:51-56 is the sole way any user gets role: admin (via forceCreate, bypassing mass-assignment protection by design). The users migration's role column (enum('role', ['customer', 'admin'])) has no companion is_active/disabled_at column, and no controller under app/Http/Controllers exposes a route that updates a user's role or suspends an account. If an admin's credentials are compromised or an admin leaves, the only remediation today is direct database/server access -- there is no product surface at all for revoking access. Fix by adding an admin-only endpoint (and Team Management UI action) to demote an admin to customer or deactivate the account, guarded so an admin can't demote/deactivate themselves down to zero remaining admins.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Product catalog search has no price-range or size/color filter even though the schema and admin UI fully support both',
                'description' => "ProductController::index (app/Http/Controllers/Api/ProductController.php:13-42) only accepts search, sort, and page query params -- sort is limited to newest/price_asc/price_desc (resources/js/pages/Catalog.jsx:14) -- with no way to filter by base_price range or by a variant's size/color, both first-class columns already eager-loaded per-product via ->with(['variants']). Every other list surface in the app (admin product/coupon/audit-log lists) has real filtering; the one customer-facing catalog browse never did, forcing shoppers to scan the full result set by eye for a specific size, color, or price band. Fix by adding min_price/max_price and size/color query params to ProductController::index (filtering via whereHas('variants', ...)) plus matching filter controls on Catalog.jsx, cache-keyed the same way search/sort/page already are.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
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
            'Product catalog has no bilingual name/description fields, so every product name and description ships in English only regardless of site language',
            "No in-app way to revoke or demote an admin account -- the only path to creating one is a console command, and there's no deactivate/role-change endpoint anywhere",
            'Product catalog search has no price-range or size/color filter even though the schema and admin UI fully support both',
        ])->delete();
    }
};
