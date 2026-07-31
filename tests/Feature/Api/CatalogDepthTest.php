<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers database/migrations/2026_08_07_181000_seed_catalog_depth_new_products.php —
 * the real data migration that grows the catalog from 7 to 14 products in production
 * (see CLAUDE.md: DatabaseSeeder.php alone never reaches production, only migrations do).
 *
 * Note on what's actually testable here: the *original* 7 products (minimal-star-tee,
 * menorah-line-tee, etc.) only ever exist via DatabaseSeeder.php, which RefreshDatabase
 * never calls (see TestCase — no $this->seed()) — only migration history is replayed.
 * So a fresh test database only ever has the 7 *new* products this migration inserts,
 * not all 14; the "7 -> 14" growth is only observable via `migrate:fresh --seed` in a
 * real dev/CI environment (the manual verification step for this task) or in
 * production after this migration runs. These tests instead assert everything that
 * *is* verifiable at the migration level: the 7 new products are well-formed, active,
 * uniquely identified, and their variants pass the same validation the original 7 do.
 */
class CatalogDepthTest extends TestCase
{
    use RefreshDatabase;

    protected const NEW_SLUGS = [
        'emunah-mark-tee',
        'bracha-mark-tee',
        'tikvah-script-hoodie',
        'ahava-mark-tee',
        'simcha-mark-tee',
        'emet-mark-tee',
        'or-mark-hoodie',
    ];

    public function test_the_public_catalog_lists_the_seven_migration_seeded_products(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonPath('meta.total', 7)
            ->assertJsonCount(7, 'data');

        $slugs = collect($response->json('data'))->pluck('slug');
        foreach (self::NEW_SLUGS as $slug) {
            $this->assertTrue($slugs->contains($slug), "Expected \"{$slug}\" in the public catalog listing.");
        }
    }

    public function test_each_new_hebrew_motif_product_is_active_with_a_unique_slug_and_sku(): void
    {
        $skus = [];

        foreach (self::NEW_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->first();

            $this->assertNotNull($product, "Expected a seeded product with slug \"{$slug}\".");
            $this->assertSame('active', $product->status);
            $this->assertNotEmpty($product->name);
            $this->assertNotEmpty($product->description);
            $this->assertMatchesRegularExpression('/^TS-\d{3}$/', $product->sku);

            $skus[] = $product->sku;
        }

        $this->assertSame($skus, array_unique($skus), 'Every new product SKU must be unique.');
    }

    public function test_the_migration_creates_exactly_the_seven_new_products_and_nothing_else(): void
    {
        $this->assertSame(7, Product::count());
        $this->assertSame(self::NEW_SLUGS, Product::orderBy('id')->pluck('slug')->all());
    }

    public function test_every_new_products_variants_have_unique_skus_following_the_existing_pattern(): void
    {
        foreach (self::NEW_SLUGS as $slug) {
            $product = Product::where('slug', $slug)->firstOrFail();
            $variants = $product->variants;

            $this->assertGreaterThanOrEqual(4, $variants->count());

            $skus = $variants->pluck('sku')->all();
            $this->assertSame($skus, array_unique($skus), "Variant SKUs for \"{$slug}\" must be unique.");

            foreach ($variants as $variant) {
                $this->assertMatchesRegularExpression(
                    '/^'.preg_quote($product->sku, '/').'-(S|M|L|XL)-[A-Z]{3}$/',
                    $variant->sku
                );
            }
        }
    }

    public function test_variant_skus_are_unique_across_the_entire_catalog(): void
    {
        $skus = ProductVariant::pluck('sku')->all();

        $this->assertSame($skus, array_unique($skus), 'No product_variants.sku value should repeat across the catalog.');
        $this->assertGreaterThan(0, count($skus));
    }

    public function test_a_new_products_variant_passes_the_existing_admin_validation_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::where('slug', 'emunah-mark-tee')->firstOrFail();

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/variants",
            [
                'size' => 'XL',
                'color' => 'Olive',
                'sku' => 'TS-EMUNAH-TEST-1',
                'stock_quantity' => 10,
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('data.size', 'XL')
            ->assertJsonPath('data.color', 'Olive');
    }

    public function test_a_duplicate_size_color_combo_on_a_new_product_is_still_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::where('slug', 'ahava-mark-tee')->firstOrFail();
        $existing = $product->variants()->first();

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/variants",
            [
                'size' => $existing->size,
                'color' => $existing->color,
                'sku' => 'TS-DUPLICATE-TEST',
                'stock_quantity' => 5,
            ]
        );

        $response->assertStatus(422);
    }
}
