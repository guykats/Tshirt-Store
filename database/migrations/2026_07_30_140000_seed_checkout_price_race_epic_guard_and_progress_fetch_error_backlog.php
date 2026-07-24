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
                'title' => 'Checkout locks stock against a concurrent price change but not the price itself, so a customer can be charged a stale amount',
                'description' => "CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php) reads \$variant->price_override / \$variant->product->base_price into \$unitPrice at line 139, before the DB::transaction() opens, and only re-fetches the variant with lockForUpdate() afterward (line 147) to re-check stock_quantity — the locked row's price is never re-read. If an admin edits a variant's price_override or a product's base_price in the window between the initial unlocked read and the transaction's row lock, the order still gets created with the stale \$unitPrice (used unchanged at line 203 for order_items.unit_price and baked into subtotal/total_amount), so the customer is charged an amount that no longer matches the live price. Fix: recompute \$unitPrice (and subtotal/discount/total) from \$locked inside the transaction instead of the pre-lock \$variant, the same way stock_quantity is already re-checked against the locked row.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'EpicController::approve() and reject() have no status guard, so an already-approved epic with live tasks can be silently rejected',
                'description' => "EpicController::approve() and ::reject() (app/Http/Controllers/Api/EpicController.php, lines 29-69) both call \$epic->update(['status' => ...]) unconditionally, with no check on the epic's current status — the same gap already tracked for delay() on this board, but on the other two decision endpoints. Because approve()/reject() carry no current-status precondition, an admin (or a stale double-submit from the Epics.jsx UI) can reject() an epic that was already approved and already broken into project_tasks (epic_id set on real rows), silently flipping it to rejected with zero cascading signal to those now-orphaned tasks, or re-approve() an already-rejected epic and overwrite its decided_by/decided_at history. Fix: add a status precondition to both (e.g. only allow approve() from 'proposed' and reject() from 'proposed' or 'approved' with an explicit warning/cascade when tasks already exist), mirroring whatever guard gets added to delay().",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProjectProgress.jsx's board and PM-automation-toggle fetches have no .catch(), so a failed load looks like an empty/off board",
                'description' => "ProjectProgress.jsx's load() (line 27) and loadAutomation() (line 35) both call api.get(...).then(...) with no .catch(), on the very dashboard page this repo's own PM workflow depends on for visibility into task/epic state and the automation on/off toggle. A failed /api/project-tasks request leaves tasks/counts at their empty initial state with no error shown, indistinguishable from a genuinely empty board; a failed /api/pm-agent-automation request leaves automation null, which the existing toggleAutomation() button logic likely treats as 'not configured' rather than 'failed to load', misrepresenting the automation's real enabled/disabled state to whoever is looking at the board. Fix: add .catch() to both, matching the error-state pattern already used elsewhere on this board (e.g. its own approved_for_dev toggle actions already surface errors).",
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
        DB::table('project_tasks')->where('title', 'Checkout locks stock against a concurrent price change but not the price itself, so a customer can be charged a stale amount')->delete();
        DB::table('project_tasks')->where('title', 'EpicController::approve() and reject() have no status guard, so an already-approved epic with live tasks can be silently rejected')->delete();
        DB::table('project_tasks')->where('title', "ProjectProgress.jsx's board and PM-automation-toggle fetches have no .catch(), so a failed load looks like an empty/off board")->delete();
    }
};
