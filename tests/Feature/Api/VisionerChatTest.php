<?php

namespace Tests\Feature\Api;

use App\Models\Epic;
use App\Models\User;
use App\Models\VisionerChatMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisionerChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.anthropic.api_key' => 'test-anthropic-key']);
    }

    protected function textResponse(string $text): array
    {
        return [
            'id' => 'msg_'.uniqid(),
            'type' => 'message',
            'role' => 'assistant',
            'content' => [['type' => 'text', 'text' => $text]],
            'stop_reason' => 'end_turn',
        ];
    }

    public function test_customers_cannot_view_or_post_to_the_chat(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/visioner-chat')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/visioner-chat', ['content' => 'hi'])->assertForbidden();
    }

    public function test_guests_cannot_view_or_post_to_the_chat(): void
    {
        $this->getJson('/api/visioner-chat')->assertUnauthorized();
        $this->postJson('/api/visioner-chat', ['content' => 'hi'])->assertUnauthorized();
    }

    public function test_an_admin_can_view_the_conversation_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        VisionerChatMessage::create(['role' => 'user', 'content' => 'Hello']);
        VisionerChatMessage::create(['role' => 'assistant', 'content' => 'Hi there.']);

        $response = $this->actingAs($admin)->getJson('/api/visioner-chat');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_sending_a_message_stores_it_and_the_assistant_reply(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response($this->textResponse('Worth exploring — tell me more about who it\'s for.')),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/visioner-chat', [
            'content' => 'What do you think about a loyalty program?',
        ]);

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertDatabaseHas('visioner_chat_messages', ['role' => 'user', 'content' => 'What do you think about a loyalty program?']);
        $this->assertDatabaseHas('visioner_chat_messages', ['role' => 'assistant', 'content' => 'Worth exploring — tell me more about who it\'s for.']);
    }

    public function test_a_tool_use_round_creates_a_proposed_epic_and_links_it_to_the_reply(): void
    {
        Epic::query()->delete();

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'id' => 'msg_1',
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_1',
                        'name' => 'propose_epic',
                        'input' => ['title' => 'Gift card program', 'description' => 'Let customers buy and redeem gift cards.'],
                    ]],
                    'stop_reason' => 'tool_use',
                ])
                ->push($this->textResponse('Done — I put "Gift card program" on the Epics board for you to review.')),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/visioner-chat', [
            'content' => 'That sounds ready, go ahead and propose it.',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('epics', ['title' => 'Gift card program', 'status' => 'proposed', 'agent_name' => 'Visioner Agent']);

        $assistantMessage = VisionerChatMessage::where('role', 'assistant')->latest('id')->first();
        $this->assertNotNull($assistantMessage->epic_id);
        $this->assertSame('Gift card program', $assistantMessage->epic->title);
    }

    public function test_proposing_an_epic_with_a_duplicate_title_does_not_create_a_second_row(): void
    {
        Epic::query()->delete();
        Epic::create(['title' => 'Gift card program', 'status' => 'proposed', 'agent_name' => 'Visioner Agent']);

        Http::fake([
            'api.anthropic.com/*' => Http::sequence()
                ->push([
                    'id' => 'msg_1',
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [[
                        'type' => 'tool_use',
                        'id' => 'toolu_1',
                        'name' => 'propose_epic',
                        'input' => ['title' => 'Gift card program', 'description' => 'Duplicate attempt.'],
                    ]],
                    'stop_reason' => 'tool_use',
                ])
                ->push($this->textResponse('That one is already on the board.')),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->postJson('/api/visioner-chat', ['content' => 'Propose the gift card idea.'])->assertOk();

        $this->assertSame(1, Epic::where('title', 'Gift card program')->count());
    }
}
