<?php

namespace Tests\Feature\Api;

use App\Models\DesignSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_the_suggestions_index(): void
    {
        $this->getJson('/api/design-suggestions')->assertUnauthorized();
    }

    public function test_customers_cannot_view_the_suggestions_index(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/design-suggestions')->assertForbidden();
    }

    public function test_admins_can_view_the_suggestions_index_latest_batch_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        DesignSuggestion::create(['batch_date' => '2026-07-01', 'motif' => 'chai', 'status' => 'pending']);
        DesignSuggestion::create(['batch_date' => '2026-07-03', 'motif' => 'hamsa', 'status' => 'pending']);

        $response = $this->actingAs($admin)->getJson('/api/design-suggestions');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame('2026-07-03', $response->json('data.0.batch_date'));
    }

    public function test_guests_cannot_generate_a_batch_on_demand(): void
    {
        $this->postJson('/api/design-suggestions/generate')->assertUnauthorized();
    }

    public function test_customers_cannot_generate_a_batch_on_demand(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->postJson('/api/design-suggestions/generate')->assertForbidden();
        $this->assertSame(0, DesignSuggestion::query()->count());
    }

    public function test_an_admin_can_generate_a_batch_on_demand(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/design-suggestions/generate');

        $response->assertOk()->assertJsonCount(20, 'data');
        $this->assertSame(20, DesignSuggestion::query()->count());
    }

    public function test_guests_and_customers_cannot_keep_or_discard_a_suggestion(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'pending']);

        $this->postJson("/api/design-suggestions/{$suggestion->id}/keep")->assertUnauthorized();
        $this->postJson("/api/design-suggestions/{$suggestion->id}/discard")->assertUnauthorized();

        $this->actingAs($customer)->postJson("/api/design-suggestions/{$suggestion->id}/keep")->assertForbidden();
        $this->actingAs($customer)->postJson("/api/design-suggestions/{$suggestion->id}/discard")->assertForbidden();

        $this->assertDatabaseHas('design_suggestions', ['id' => $suggestion->id, 'status' => 'pending']);
    }

    public function test_an_admin_can_keep_a_suggestion_without_creating_a_design(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'hamsa', 'status' => 'pending']);

        $response = $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/keep");

        $response->assertOk()->assertJsonPath('data.status', 'kept');
        $this->assertSame(0, \App\Models\Design::query()->count());

        $suggestion->refresh();
        $this->assertSame('kept', $suggestion->status);
        $this->assertNull($suggestion->promoted_design_id);
    }

    public function test_an_admin_can_publish_a_kept_suggestion_and_it_promotes_a_pending_approval_design(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'hamsa', 'status' => 'kept']);

        $response = $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/publish");

        // 201, not 200: DesignResource wraps a Design row that
        // wasRecentlyCreated, and Laravel's ResourceResponse automatically
        // upgrades the status code for that case (see ResourceResponse).
        $response->assertCreated()->assertJsonPath('data.status', 'pending_approval');
        $this->assertDatabaseHas('designs', [
            'status' => 'pending_approval',
            'mockup_url' => 'hamsa',
        ]);

        $suggestion->refresh();
        $this->assertSame('published', $suggestion->status);
        $this->assertNotNull($suggestion->promoted_design_id);
        $this->assertDatabaseHas('designs', ['id' => $suggestion->promoted_design_id]);
    }

    public function test_publish_rejects_a_suggestion_that_is_not_kept(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'pending']);

        $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/publish")->assertStatus(422);
        $this->assertDatabaseHas('design_suggestions', ['id' => $suggestion->id, 'status' => 'pending']);
    }

    public function test_guests_and_customers_cannot_publish_a_suggestion(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'kept']);

        $this->postJson("/api/design-suggestions/{$suggestion->id}/publish")->assertUnauthorized();
        $this->actingAs($customer)->postJson("/api/design-suggestions/{$suggestion->id}/publish")->assertForbidden();

        $this->assertDatabaseHas('design_suggestions', ['id' => $suggestion->id, 'status' => 'kept']);
    }

    public function test_an_admin_can_publish_all_kept_suggestions_in_the_latest_batch(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        DesignSuggestion::create(['batch_date' => '2026-07-01', 'motif' => 'star_of_david', 'status' => 'kept']);
        $keptOne = DesignSuggestion::create(['batch_date' => '2026-07-03', 'motif' => 'chai', 'status' => 'kept']);
        $keptTwo = DesignSuggestion::create(['batch_date' => '2026-07-03', 'motif' => 'hamsa', 'status' => 'kept']);
        $pending = DesignSuggestion::create(['batch_date' => '2026-07-03', 'motif' => 'menorah', 'status' => 'pending']);

        $response = $this->actingAs($admin)->postJson('/api/design-suggestions/publish-all');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertSame(2, \App\Models\Design::query()->count());

        $keptOne->refresh();
        $keptTwo->refresh();
        $pending->refresh();
        $this->assertSame('published', $keptOne->status);
        $this->assertSame('published', $keptTwo->status);
        $this->assertSame('pending', $pending->status);
        $this->assertNotNull($keptOne->promoted_design_id);
        $this->assertNotNull($keptTwo->promoted_design_id);
    }

    public function test_publish_all_is_a_no_op_when_there_are_no_kept_suggestions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'pending']);

        $response = $this->actingAs($admin)->postJson('/api/design-suggestions/publish-all');

        $response->assertOk()->assertJsonCount(0, 'data');
        $this->assertSame(0, \App\Models\Design::query()->count());
    }

    public function test_guests_and_customers_cannot_publish_all(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'kept']);

        $this->postJson('/api/design-suggestions/publish-all')->assertUnauthorized();
        $this->actingAs($customer)->postJson('/api/design-suggestions/publish-all')->assertForbidden();

        $this->assertSame(0, \App\Models\Design::query()->count());
    }

    public function test_an_admin_can_discard_a_suggestion(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'pending']);

        $response = $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/discard");

        $response->assertOk()->assertJsonPath('data.status', 'discarded');
        $this->assertDatabaseHas('design_suggestions', ['id' => $suggestion->id, 'status' => 'discarded']);
    }

    public function test_a_suggestion_that_is_not_pending_cannot_be_kept_or_discarded_again(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $suggestion = DesignSuggestion::create(['batch_date' => now(), 'motif' => 'chai', 'status' => 'kept']);

        $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/keep")->assertStatus(422);
        $this->actingAs($admin)->postJson("/api/design-suggestions/{$suggestion->id}/discard")->assertStatus(422);
    }
}
