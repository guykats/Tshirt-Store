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
                'title' => "VisionerChatController::proposeEpic() never caps title length, so a long LLM-generated title crashes the chat with a raw 500",
                'description' => "app/Http/Controllers/Api/VisionerChatController.php:72-94's proposeEpic() only checks that title is non-empty and not a literal duplicate — it never applies a max:255 (or any) length check, even though every other admin-authored title/name field in the app is validated with an explicit max length before insert. epics.title is a plain string (varchar 255) column, and the propose_epic tool's input_schema (same file, lines 101-108) has no maxLength on title either, so nothing constrains what the Anthropic model sends. A title longer than 255 characters raises an uncaught QueryException from Epic::create(), which propagates straight up through the tool-handler call in AnthropicClient::converse() with no try/catch, turning a live admin chat message into a raw 500 instead of a clean ['error' => ...] tool result the model could recover from. Fix: validate/truncate \$title (e.g. Str::limit(\$title, 255) or return a tool error over 255 chars) before Epic::create(), matching how every other user/LLM-authored string field elsewhere in the app is bounded before it reaches the DB.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "DesignSuggestionController::discard() only accepts a 'pending' suggestion, so a mistakenly-kept suggestion can never be un-kept",
                'description' => "app/Http/Controllers/Api/DesignSuggestionController.php:163's discard() hard-guards status !== 'pending', and keep() (same file, line 63) is a one-way transition into status = 'kept' with no corresponding 'un-keep' action anywhere in the controller. Discover.jsx mirrors this: once a suggestion is 'kept', the only button rendered for that card is 'Publish' — there is no discard/un-keep control in that branch at all. Concrete failure: an admin reviewing the nightly Discover batch misclicks 'Keep' (or changes their mind) on a suggestion they don't want. There is now no way to get that row out of 'kept' short of clicking 'Publish' anyway — which creates a real, unwanted pending_approval Design row via promote() (lines 139-157) that then has to be separately rejected through the entirely different DesignController::reject() workflow, polluting the admin Pending Designs queue with something that was never supposed to exist. Fix: let discard() also accept status === 'kept' (reverting straight to 'discarded', no Design ever created), and add the matching button back for kept cards in Discover.jsx.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "WishlistController::index returns a customer's entire wishlist with no pagination or cap, unlike every other per-user list endpoint",
                'description' => "app/Http/Controllers/Api/WishlistController.php:17-28's index() runs \$request->user()->wishlistItems()->with('product.design', 'product.variants')->latest()->get() with no paginate(), limit(), or any cap — every saved item is eager-loaded (each pulling its product, that product's design, and every variant) and returned in a single response on every Wishlist page load. Every comparable per-user list in the app bounds itself — Orders.jsx's backend paginates 20/page, ReviewController::index caps at 100, ProjectTaskController::index hard-caps at 200 — WishlistController::index has none of these. Concrete failure: a customer who has wishlisted dozens/hundreds of products over time gets a single ever-growing JSON payload with N+2 relations eager-loaded per item on every visit to /wishlist, with no server-side limit protecting against pathological growth. Fix: paginate wishlistItems() the same way OrderController::index does, and update Wishlist.jsx to page through results instead of assuming the full list arrives in one response.",
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
            "VisionerChatController::proposeEpic() never caps title length, so a long LLM-generated title crashes the chat with a raw 500",
            "DesignSuggestionController::discard() only accepts a 'pending' suggestion, so a mistakenly-kept suggestion can never be un-kept",
            "WishlistController::index returns a customer's entire wishlist with no pagination or cap, unlike every other per-user list endpoint",
        ])->delete();
    }
};
