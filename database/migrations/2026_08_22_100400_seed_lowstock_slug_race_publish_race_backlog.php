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
                'title' => 'Low-stock alert never re-arms when a variant is restocked to exactly the threshold',
                'description' => "app/Http/Controllers/Api/Admin/ProductVariantController.php:57 and app/Services/OrderStockService.php:54 both clear low_stock_alerted_at using a strict `stock_quantity > InventoryController::DEFAULT_THRESHOLD` check, while CheckoutController::store (line 161) fires the alert whenever `stock_quantity <= DEFAULT_THRESHOLD`. If an admin restocks a variant to exactly the threshold value (5) via the admin edit form, or a cancel/refund restores stock back to exactly 5, the `>` check is false so low_stock_alerted_at is never cleared. Every subsequent sale keeps the variant at or below 5, so checkout's `low_stock_alerted_at === null` re-arm condition never becomes true again — the admin permanently stops getting low-stock notifications for that variant unless someone happens to restock past 5 instead of to exactly 5. Fix: change both clearing checks from `>` to `>=` so they match checkout's `<=` firing condition.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Admin product creation has an unhandled slug race, unlike its sibling create endpoints',
                'description' => "app/Http/Controllers/Api/Admin/ProductController.php's uniqueSlug() (lines ~140-152) loops `Product::where('slug', \$slug)->exists()` and only inserts afterward — a check-then-insert race. products.slug has a real unique() DB constraint, so this can't silently create a duplicate, but two admins (or one admin double-submitting) creating two products with the same name at the same moment can both pass the exists() check with the same computed slug before either Product::create() commits. The second store() call then throws a raw QueryException that bubbles up as an unhandled 500, exactly the failure mode this codebase already guards against elsewhere for the identical exists-then-insert pattern (ReviewController::store and WishlistController::store both wrap their create() in try/catch (QueryException)). Admin\\ProductController::store has no such guard. Fix by wrapping the create() call in the same try/catch (QueryException) + retry-with-suffix pattern already used in ReviewController/WishlistController.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Publishing a design suggestion has no row lock, so a race can create two Design rows from one suggestion',
                'description' => "app/Http/Controllers/Api/DesignSuggestionController.php's publish()/publishAll()/promote() (lines ~87-157): publish() checks `\$designSuggestion->status !== 'kept'` as a plain unlocked read, then calls promote() inside DB::transaction() — but promote() never re-checks or row-locks the suggestion before calling Design::create(...). publishAll() has the identical gap for every suggestion in the latest kept batch. Two concurrent requests for the same suggestion — an admin double-clicking Publish, or an individual Publish landing at the same instant as a bulk Publish All Kept click — can both read status === 'kept' before either commits, and both execute promote(), each creating its own Design row and each overwriting the suggestion's promoted_design_id/status in turn. Result: two live, duplicate Design records land in the pending_approval queue for what should have been a single suggestion. Fix by row-locking the suggestion (lockForUpdate()) and re-checking status === 'kept' inside the transaction before creating the Design, the same lock-then-recheck pattern already used in OrderController::cancel()/refund().",
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
            'Low-stock alert never re-arms when a variant is restocked to exactly the threshold',
            'Admin product creation has an unhandled slug race, unlike its sibling create endpoints',
            'Publishing a design suggestion has no row lock, so a race can create two Design rows from one suggestion',
        ])->delete();
    }
};
