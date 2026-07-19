<?php

namespace Tests\Feature\Api;

use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialTest extends TestCase
{
    use RefreshDatabase;

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'author_name' => 'Jamie L.',
            'author_context_en' => 'Customer — Chicago, IL',
            'author_context_he' => 'לקוחה — שיקגו, אילינוי',
            'quote_en' => 'Great fit and the packaging felt thoughtful.',
            'quote_he' => 'מידה מצוינת והאריזה הרגישה מטופחת.',
            'sort_order' => 0,
            'is_active' => true,
        ], $overrides);
    }

    public function test_guests_can_read_only_active_testimonials_in_sort_order(): void
    {
        // The historical seed migration (2026_07_19_232000_seed_testimonials) already
        // inserts rows here — RefreshDatabase replays every data migration, so a test
        // asserting an exact count has to clear the table first.
        Testimonial::query()->delete();
        Testimonial::create($this->payload(['author_name' => 'Second', 'sort_order' => 2]));
        Testimonial::create($this->payload(['author_name' => 'First', 'sort_order' => 1]));
        Testimonial::create($this->payload(['author_name' => 'Hidden', 'sort_order' => 0, 'is_active' => false]));

        $response = $this->getJson('/api/testimonials');

        $response->assertOk()->assertJsonCount(2, 'data');
        $names = collect($response->json('data'))->pluck('author_name');
        $this->assertSame(['First', 'Second'], $names->all());
    }

    public function test_guests_cannot_manage_create_update_or_delete_testimonials(): void
    {
        $testimonial = Testimonial::create($this->payload());

        $this->getJson('/api/testimonials/manage')->assertUnauthorized();
        $this->postJson('/api/testimonials', $this->payload())->assertUnauthorized();
        $this->patchJson("/api/testimonials/{$testimonial->id}", $this->payload())->assertUnauthorized();
        $this->deleteJson("/api/testimonials/{$testimonial->id}")->assertUnauthorized();
    }

    public function test_customers_cannot_manage_testimonials(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $testimonial = Testimonial::create($this->payload());

        $this->actingAs($customer)->getJson('/api/testimonials/manage')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/testimonials', $this->payload())->assertForbidden();
        $this->actingAs($customer)->patchJson("/api/testimonials/{$testimonial->id}", $this->payload())->assertForbidden();
        $this->actingAs($customer)->deleteJson("/api/testimonials/{$testimonial->id}")->assertForbidden();
    }

    public function test_an_admin_can_create_update_and_delete_a_testimonial(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin)->postJson('/api/testimonials', $this->payload());
        $createResponse->assertCreated()->assertJsonPath('data.author_name', 'Jamie L.');

        $id = $createResponse->json('data.id');

        $this->actingAs($admin)->patchJson("/api/testimonials/{$id}", $this->payload(['author_name' => 'Jamie Updated']))
            ->assertOk()
            ->assertJsonPath('data.author_name', 'Jamie Updated');

        $this->assertDatabaseHas('testimonials', ['id' => $id, 'author_name' => 'Jamie Updated']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'testimonial.created']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'testimonial.updated']);

        $this->actingAs($admin)->deleteJson("/api/testimonials/{$id}")->assertOk();
        $this->assertDatabaseMissing('testimonials', ['id' => $id]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'testimonial.deleted']);
    }

    public function test_admin_manage_listing_includes_inactive_testimonials(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Testimonial::query()->delete();
        Testimonial::create($this->payload(['author_name' => 'Active', 'is_active' => true]));
        Testimonial::create($this->payload(['author_name' => 'Inactive', 'is_active' => false]));

        $response = $this->actingAs($admin)->getJson('/api/testimonials/manage');

        $response->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_creating_a_testimonial_requires_both_languages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/testimonials', $this->payload(['quote_he' => '']));

        $response->assertStatus(422)->assertJsonValidationErrors('quote_he');
    }
}
