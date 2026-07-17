<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VisionerChatMessageResource;
use App\Models\Epic;
use App\Models\ProjectTask;
use App\Models\VisionerChatMessage;
use App\Services\AnthropicClient;
use Illuminate\Http\Request;

class VisionerChatController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $messages = VisionerChatMessage::query()->with('epic')->orderBy('created_at')->limit(200)->get();

        return VisionerChatMessageResource::collection($messages);
    }

    public function store(Request $request, AnthropicClient $anthropic)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:4000'],
        ]);

        $userMessage = VisionerChatMessage::create([
            'user_id' => $request->user()->id,
            'role' => 'user',
            'content' => $data['content'],
        ]);

        $history = VisionerChatMessage::query()
            ->orderBy('created_at')
            ->limit(40)
            ->get()
            ->map(fn (VisionerChatMessage $m) => ['role' => $m->role, 'content' => $m->content])
            ->all();

        $result = $anthropic->converse(
            system: $this->systemPrompt(),
            messages: $history,
            tools: $this->tools(),
            toolHandlers: [
                'propose_epic' => fn (array $input) => $this->proposeEpic($input),
            ],
        );

        $proposedEpicId = null;
        foreach ($result['tool_calls'] as $call) {
            if ($call['name'] === 'propose_epic' && isset($call['result']['epic_id'])) {
                $proposedEpicId = $call['result']['epic_id'];
                break;
            }
        }

        $assistantMessage = VisionerChatMessage::create([
            'user_id' => null,
            'role' => 'assistant',
            'content' => $result['text'] !== '' ? $result['text'] : '(No reply text — check the epics board for anything just proposed.)',
            'epic_id' => $proposedEpicId,
        ]);

        return VisionerChatMessageResource::collection([$userMessage, $assistantMessage->load('epic')]);
    }

    protected function proposeEpic(array $input): array
    {
        $title = trim((string) ($input['title'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));

        if ($title === '') {
            return ['error' => 'title is required'];
        }

        if (Epic::where('title', $title)->exists()) {
            return ['error' => 'An epic with that exact title already exists — ask the owner if they mean a different one, or propose a more specific title.'];
        }

        $epic = Epic::create([
            'title' => $title,
            'description' => $description,
            'agent_name' => 'Visioner Agent',
            'status' => 'proposed',
            'priority' => 0,
        ]);

        return ['epic_id' => $epic->id, 'status' => 'proposed'];
    }

    protected function tools(): array
    {
        return [[
            'name' => 'propose_epic',
            'description' => 'Propose a new strategic epic onto the project\'s Epics board (status: proposed). Only call this once the conversation has converged on a concrete, well-scoped idea — the owner still has to explicitly choose/reject/delay it from the dashboard, so proposing is low-stakes, but don\'t spam proposals for vague or half-formed ideas.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string', 'description' => 'Short, concrete epic title.'],
                    'description' => ['type' => 'string', 'description' => 'What it is, why it matters, grounded in the conversation.'],
                ],
                'required' => ['title', 'description'],
            ],
        ]];
    }

    protected function systemPrompt(): string
    {
        $epics = Epic::query()->orderByDesc('updated_at')->limit(15)->get(['title', 'status']);
        $epicLines = $epics->isEmpty()
            ? 'None yet.'
            : $epics->map(fn (Epic $e) => "- [{$e->status}] {$e->title}")->implode("\n");

        $taskCounts = ProjectTask::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return <<<PROMPT
            You are the Visioner Agent for Tshirt Store, a bilingual (English/Hebrew) Jewish-identity
            apparel e-commerce startup. You are talking live, in a chat, with the project's owner.

            Your job here is strategic conversation and proposal, not implementation. Help the owner
            think through ideas — growth channels, new product capabilities, positioning, what to
            prioritize — and when an idea is concrete and well-scoped enough, use the propose_epic tool
            to put it on the Epics board as `status: proposed`. The owner still explicitly chooses,
            rejects, or delays every epic from the dashboard — you are not deciding anything by
            proposing it, so don't be falsely modest about proposing when an idea is genuinely ready.
            Do not propose vague or half-formed ideas; ask clarifying questions instead until it's
            concrete enough to hand to a PM to break into tasks.

            You do not write code, create project_tasks, or take any other action — only propose_epic.

            Current state of the board, for grounding (don't just repeat this back verbatim):

            Recent epics:
            {$epicLines}

            project_tasks counts by status: {$taskCounts->toJson()}

            Keep replies conversational and concise — this is a chat, not a report.
            PROMPT;
    }
}
