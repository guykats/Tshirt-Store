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
                'title' => 'ProductImageController::reorder has no transaction, risking a corrupted gallery order',
                'description' => "app/Http/Controllers/Api/Admin/ProductImageController.php:82-84 loops over the submitted image_ids and issues one separate `ProductImage::where('id', \$id)->update(['position' => \$position])` call per image, with no `DB::transaction` wrapping the loop — unlike every stock/coupon-touching write path in this app, which is deliberately transactional. Failure scenario: a DB blip or two admins reordering the same product's gallery concurrently causes the loop to fail partway through, leaving the gallery with duplicate or inconsistent `position` values and no rollback to the pre-reorder order. Fix by wrapping the update loop in `DB::transaction()`.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'useDocumentMeta never clears a stale meta description across route navigation',
                'description' => "resources/js/hooks/useDocumentMeta.js only calls setMetaDescription(description) when description is truthy; it never resets or removes the tag when a later page calls the hook with no second argument (most pages — Orders.jsx, Login.jsx, Dashboard.jsx — only pass a title). Failure scenario: a visitor reads a ProductDetail page (which passes the product's real description), then navigates to any page that omits one — the previous product's description stays in `<meta name=\"description\">` indefinitely, serving wrong content to search engines and social-link scrapers on the new page. Fix by resetting to a site-default description (or clearing the tag) whenever a page's call omits one.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'AddressController::store\'s "first address becomes default" check has a TOCTOU race, letting a customer end up with two default addresses',
                'description' => "app/Http/Controllers/Api/AddressController.php:63-68 evaluates `\$isFirstAddress = \$user->addresses()->doesntExist()` and then inserts a new address with `is_default => \$isFirstAddress`, with no row lock or transaction around the check-then-act — unlike setDefault() (lines 135-137) which atomically clears every other row's is_default inside its own DB::transaction. There is also no DB-level unique constraint enforcing at most one default address per user (confirmed absent from the addresses table migration). Failure scenario: two concurrent 'add address' requests from the same brand-new customer (e.g. a double form submit) can both read doesntExist() === true before either insert commits, producing two rows with is_default = true for the same user, leaving Checkout's default-address preselection ambiguous. Fix by wrapping the check-and-insert in a DB::transaction with a row lock, and/or adding a partial unique index on (user_id) where is_default.",
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
            'ProductImageController::reorder has no transaction, risking a corrupted gallery order',
            'useDocumentMeta never clears a stale meta description across route navigation',
            'AddressController::store\'s "first address becomes default" check has a TOCTOU race, letting a customer end up with two default addresses',
        ])->delete();
    }
};
