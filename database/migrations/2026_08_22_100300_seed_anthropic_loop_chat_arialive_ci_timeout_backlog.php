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
                'title' => "AnthropicClient::converse() can exhaust its tool-round budget and silently drop the model's final answer",
                'description' => 'app/Services/AnthropicClient.php:46-80: the `for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++)` loop (MAX_TOOL_ROUNDS = 5) calls send() once per iteration and only appends text blocks from whatever response it just got. If round 5 (the last allowed iteration) itself comes back with stop_reason === \'tool_use\', the code still executes the tool handlers and appends the tool results to $messages (lines 58-76), but the for-loop condition then fails and the method returns immediately — it never sends that final $messages state back to the API for a follow-up response. Failure scenario: VisionerChatController::store\'s Visioner Agent chat needs 5 tool calls to gather the context it wants (a plausible count once an epic-proposal or board-lookup tool chain runs long), computes the results, but the user-visible reply comes back as empty/whatever partial text happened to be in round 5\'s content — the actual synthesized answer that should follow those tool results is silently discarded, and nothing logs or surfaces that the round budget was hit. Fix by either sending one final round without tools after the loop exits due to exhaustion, or detecting exhaustion and returning a distinct signal instead of silently truncating.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "VisionerChat's message log has no aria-live region, breaking the app's own live-update convention",
                'description' => "resources/js/pages/VisionerChat.jsx:58 — the scrollable div holding the conversation (`<div className=\"mb-4 flex-1 overflow-y-auto ...\">`) has no `role=\"status\"`/`aria-live` attribute, even though this is the app's only live-updating conversational UI and the app already has an established `role=\"status\" aria-live=\"polite\"` pattern elsewhere (resources/js/pages/Discover.jsx:272, resources/js/components/RouteLoading.jsx:11, resources/js/components/Skeleton.jsx:17,36). Failure scenario: a screen-reader user submits a chat message via send() (line 32); the assistant's reply is appended to `messages` and the view scrolls (line 28's scrollIntoView), but because the container isn't a live region, nothing is announced — the user has to manually re-navigate into the message list to discover whether or that a reply even arrived. Fix by adding role=\"log\" (or status) plus aria-live=\"polite\" to the message container, consistent with the app's existing convention.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'tests.yml has no timeout-minutes, so a single hung test can wedge a runner for up to 6 hours across a 3-way PHP matrix',
                'description' => ".github/workflows/tests.yml:17-24 defines the `tests` job with `fail-fast: true` and a `php: [8.3, 8.4, 8.5]` matrix but sets no `timeout-minutes` anywhere in the job, so each matrix leg defaults to GitHub Actions' 360-minute (6-hour) job cap. None of AnthropicClient, PayPalClient, or GitHubActionsClient's outbound HTTP calls have a request timeout (already flagged separately in the backlog as task #303), so a test that exercises one of those paths without a mock — or any other unexpected hang — doesn't fail fast, it just sits, burning CI minutes for up to 6 hours per leg (up to 18 hours total across the matrix before fail-fast's cancellation-on-first-failure has any chance to kick in, since a hang produces no failure signal to trigger it). Fix by adding a sane `timeout-minutes` (e.g. 15) to the tests job.",
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
            "AnthropicClient::converse() can exhaust its tool-round budget and silently drop the model's final answer",
            "VisionerChat's message log has no aria-live region, breaking the app's own live-update convention",
            'tests.yml has no timeout-minutes, so a single hung test can wedge a runner for up to 6 hours across a 3-way PHP matrix',
        ])->delete();
    }
};
