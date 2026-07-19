<?php

namespace Tests\Feature\Api;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_the_public_site_settings(): void
    {
        $response = $this->getJson('/api/site-settings');

        $response->assertOk()
            ->assertJsonPath('data.hero_motif', 'star-of-david')
            ->assertJsonPath('data.accent_color', '#8c6a3f')
            ->assertJsonStructure([
                'data' => [
                    'logo_path', 'logo_url', 'accent_color',
                    'hero_tagline_en', 'hero_tagline_he',
                    'hero_subheading_en', 'hero_subheading_he',
                    'hero_motif',
                ],
            ]);
    }

    public function test_settings_singleton_is_created_on_demand_if_the_row_is_missing(): void
    {
        SiteSetting::query()->delete();

        $response = $this->getJson('/api/site-settings');

        $response->assertOk()->assertJsonPath('data.hero_motif', 'star-of-david');
        $this->assertDatabaseCount('site_settings', 1);
    }

    public function test_guests_cannot_update_site_settings(): void
    {
        $this->patchJson('/api/site-settings', ['accent_color' => '#111111'])->assertUnauthorized();
    }

    public function test_customers_cannot_update_site_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->patchJson('/api/site-settings', $this->validPayload())->assertForbidden();
    }

    public function test_an_admin_can_update_site_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patchJson('/api/site-settings', array_merge(
            $this->validPayload(),
            ['hero_tagline_en' => 'A brand new tagline'],
        ));

        $response->assertOk()
            ->assertJsonPath('data.hero_tagline_en', 'A brand new tagline');

        $this->assertDatabaseHas('site_settings', [
            'id' => 1,
            'hero_tagline_en' => 'A brand new tagline',
        ]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'site_settings.updated']);
    }

    public function test_updating_site_settings_rejects_an_invalid_hex_color(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patchJson('/api/site-settings', array_merge(
            $this->validPayload(),
            ['accent_color' => 'not-a-color'],
        ));

        $response->assertStatus(422)->assertJsonValidationErrors('accent_color');
    }

    public function test_updating_site_settings_rejects_an_unknown_motif(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->patchJson('/api/site-settings', array_merge(
            $this->validPayload(),
            ['hero_motif' => 'not-a-real-motif'],
        ));

        $response->assertStatus(422)->assertJsonValidationErrors('hero_motif');
    }

    public function test_site_setting_current_returns_the_singleton_row(): void
    {
        $first = SiteSetting::current();
        $second = SiteSetting::current();

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('site_settings', 1);
    }

    private function validPayload(): array
    {
        return [
            'logo_path' => null,
            'accent_color' => '#8c6a3f',
            'hero_tagline_en' => 'Jewish identity, worn with quiet pride.',
            'hero_tagline_he' => 'זהות יהודית, נלבשת בגאווה שקטה.',
            'hero_subheading_en' => 'Understated apparel carrying real cultural symbols.',
            'hero_subheading_he' => 'בגדים מאופקים הנושאים סמלים תרבותיים אמיתיים.',
            'hero_motif' => 'star-of-david',
        ];
    }
}
