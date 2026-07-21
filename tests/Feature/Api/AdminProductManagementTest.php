<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeDesign(array $overrides = []): Design
    {
        return Design::create(array_merge([
            'title' => 'Test Design',
            'category' => 'cultural-signal',
            'mockup_url' => 'chai',
            'status' => 'approved',
        ], $overrides));
    }

    protected function makeProduct(array $overrides = []): Product
    {
        $design = $this->makeDesign();

        return Product::create(array_merge([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'description' => 'A test product.',
            'base_price' => 30.00,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ], $overrides));
    }

    protected function makeVariant(Product $product, array $overrides = []): ProductVariant
    {
        return $product->variants()->create(array_merge([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ], $overrides));
    }

    protected function productPayload(array $overrides = []): array
    {
        $design = $this->makeDesign(['title' => 'Payload Design']);

        return array_merge([
            'design_id' => $design->id,
            'name' => 'Minimal Aleph Tee',
            'description' => 'A single letter, centered.',
            'base_price' => 32.00,
            'currency' => 'USD',
            'sku' => 'TT-'.uniqid(),
            'status' => 'draft',
        ], $overrides);
    }

    protected function variantPayload(array $overrides = []): array
    {
        return array_merge([
            'size' => 'L',
            'color' => 'Sand',
            'sku' => 'TT-L-SND-'.uniqid(),
            'stock_quantity' => 15,
            'price_override' => null,
        ], $overrides);
    }

    public function test_guests_cannot_manage_products(): void
    {
        $product = $this->makeProduct();

        $this->getJson('/api/admin/products')->assertUnauthorized();
        $this->postJson('/api/admin/products', $this->productPayload())->assertUnauthorized();
        $this->putJson("/api/admin/products/{$product->slug}", $this->productPayload())->assertUnauthorized();
        $this->deleteJson("/api/admin/products/{$product->slug}")->assertUnauthorized();

        $variant = $this->makeVariant($product);
        $this->postJson("/api/admin/products/{$product->slug}/variants", $this->variantPayload())->assertUnauthorized();
        $this->putJson("/api/admin/products/{$product->slug}/variants/{$variant->id}", $this->variantPayload())->assertUnauthorized();
        $this->deleteJson("/api/admin/products/{$product->slug}/variants/{$variant->id}")->assertUnauthorized();
    }

    public function test_customers_cannot_manage_products(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $this->actingAs($customer)->getJson('/api/admin/products')->assertForbidden();
        $this->actingAs($customer)->postJson('/api/admin/products', $this->productPayload())->assertForbidden();
        $this->actingAs($customer)->putJson("/api/admin/products/{$product->slug}", $this->productPayload())->assertForbidden();
        $this->actingAs($customer)->deleteJson("/api/admin/products/{$product->slug}")->assertForbidden();
        $this->actingAs($customer)->postJson("/api/admin/products/{$product->slug}/variants", $this->variantPayload())->assertForbidden();
        $this->actingAs($customer)->putJson("/api/admin/products/{$product->slug}/variants/{$variant->id}", $this->variantPayload())->assertForbidden();
        $this->actingAs($customer)->deleteJson("/api/admin/products/{$product->slug}/variants/{$variant->id}")->assertForbidden();
    }

    public function test_an_admin_can_create_update_and_delete_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $createResponse = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload());
        $createResponse->assertCreated()
            ->assertJsonPath('data.name', 'Minimal Aleph Tee')
            ->assertJsonPath('data.status', 'draft');

        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $createResponse->json('data.slug'));

        $slug = $createResponse->json('data.slug');

        $updateResponse = $this->actingAs($admin)->putJson(
            "/api/admin/products/{$slug}",
            $this->productPayload(['name' => 'Minimal Aleph Tee', 'status' => 'active'])
        );
        $updateResponse->assertOk()->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('products', ['slug' => $slug, 'status' => 'active']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product.created']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product.updated']);

        $this->actingAs($admin)->deleteJson("/api/admin/products/{$slug}")->assertOk();
        $this->assertDatabaseMissing('products', ['slug' => $slug]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product.deleted']);
    }

    public function test_creating_a_product_requires_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload(['name' => '']));

        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_creating_a_product_requires_a_valid_design(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload(['design_id' => 999999]));

        $response->assertStatus(422)->assertJsonValidationErrors('design_id');
    }

    public function test_slugs_are_deduplicated_for_products_with_the_same_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $first = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload(['name' => 'Duplicate Name']));
        $second = $this->actingAs($admin)->postJson('/api/admin/products', $this->productPayload(['name' => 'Duplicate Name']));

        $first->assertCreated();
        $second->assertCreated();
        $this->assertNotSame($first->json('data.slug'), $second->json('data.slug'));
    }

    public function test_admin_manage_listing_includes_draft_and_archived_products(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeProduct(['name' => 'Active One', 'status' => 'active']);
        $this->makeProduct(['name' => 'Draft One', 'status' => 'draft']);
        $this->makeProduct(['name' => 'Archived One', 'status' => 'archived']);

        $response = $this->actingAs($admin)->getJson('/api/admin/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Active One'));
        $this->assertTrue($names->contains('Draft One'));
        $this->assertTrue($names->contains('Archived One'));
    }

    /**
     * Admin\ProductController::index paginates 50-at-a-time — a catalog with
     * exactly 51 products must still make the 51st one reachable, either via
     * page 2 or via ?search=, matching the "product #51 never disappears"
     * regression this task exists to close.
     */
    public function test_the_51st_product_is_reachable_via_page_two(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 50; $i++) {
            $this->makeProduct(['name' => "Bulk Product {$i}"]);
        }
        $last = $this->makeProduct(['name' => 'Fifty First Product', 'sku' => 'TT-UNIQUE-51']);

        $firstPage = $this->actingAs($admin)->getJson('/api/admin/products');
        $firstPage->assertOk();
        $this->assertCount(50, $firstPage->json('data'));
        $this->assertFalse(collect($firstPage->json('data'))->pluck('id')->contains($last->id));
        $this->assertSame(2, $firstPage->json('meta.last_page'));

        $secondPage = $this->actingAs($admin)->getJson('/api/admin/products?page=2');
        $secondPage->assertOk();
        $this->assertTrue(collect($secondPage->json('data'))->pluck('id')->contains($last->id));
    }

    public function test_the_51st_product_is_reachable_via_search(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 50; $i++) {
            $this->makeProduct(['name' => "Bulk Product {$i}"]);
        }
        $last = $this->makeProduct(['name' => 'Fifty First Product', 'sku' => 'TT-UNIQUE-51']);

        $bySku = $this->actingAs($admin)->getJson('/api/admin/products?search=TT-UNIQUE-51');
        $bySku->assertOk();
        $this->assertCount(1, $bySku->json('data'));
        $this->assertSame($last->id, $bySku->json('data.0.id'));

        $byName = $this->actingAs($admin)->getJson('/api/admin/products?search=Fifty First');
        $byName->assertOk();
        $this->assertCount(1, $byName->json('data'));
        $this->assertSame($last->id, $byName->json('data.0.id'));
    }

    public function test_product_listing_behavior_for_50_or_fewer_products_is_unchanged(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeProduct(['name' => 'Only Active One', 'status' => 'active']);
        $this->makeProduct(['name' => 'Only Draft One', 'status' => 'draft']);

        $response = $this->actingAs($admin)->getJson('/api/admin/products');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertSame(1, $response->json('meta.last_page'));
    }

    public function test_an_admin_can_create_update_and_delete_a_variant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $createResponse = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/variants",
            $this->variantPayload()
        );
        $createResponse->assertCreated()->assertJsonPath('data.size', 'L')->assertJsonPath('data.color', 'Sand');

        $variantId = $createResponse->json('data.id');

        $updateResponse = $this->actingAs($admin)->putJson(
            "/api/admin/products/{$product->slug}/variants/{$variantId}",
            $this->variantPayload(['stock_quantity' => 3])
        );
        $updateResponse->assertOk()->assertJsonPath('data.stock_quantity', 3);

        $this->assertDatabaseHas('product_variants', ['id' => $variantId, 'stock_quantity' => 3]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_variant.created']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_variant.updated']);

        $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->slug}/variants/{$variantId}")->assertOk();
        $this->assertDatabaseMissing('product_variants', ['id' => $variantId]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_variant.deleted']);
    }

    public function test_creating_a_variant_requires_required_fields(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/variants",
            $this->variantPayload(['sku' => ''])
        );

        $response->assertStatus(422)->assertJsonValidationErrors('sku');
    }

    public function test_creating_a_duplicate_size_color_combo_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $this->makeVariant($product, ['size' => 'L', 'color' => 'Sand']);

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/variants",
            $this->variantPayload(['size' => 'L', 'color' => 'Sand'])
        );

        $response->assertStatus(422);
    }

    public function test_a_variant_belonging_to_a_different_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);
        $variant = $this->makeVariant($productB);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$productA->slug}/variants/{$variant->id}", $this->variantPayload())
            ->assertNotFound();
    }

    public function test_deleting_a_product_with_existing_orders_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $address = $customer->addresses()->create([
            'type' => 'shipping', 'full_name' => 'Test Buyer', 'line1' => '1 Test St',
            'city' => 'New York', 'state' => 'NY', 'postal_code' => '10001',
        ]);

        $order = $customer->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->slug}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_a_variant_with_existing_orders_is_blocked(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $address = $customer->addresses()->create([
            'type' => 'shipping', 'full_name' => 'Test Buyer', 'line1' => '1 Test St',
            'city' => 'New York', 'state' => 'NY', 'postal_code' => '10001',
        ]);

        $order = $customer->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
        ]);

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->slug}/variants/{$variant->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('product_variants', ['id' => $variant->id]);
    }

    public function test_a_product_with_no_orders_can_still_be_deleted_after_its_variant_is_added(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $this->makeVariant($product);

        $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->slug}")->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_variants', ['product_id' => $product->id]);
    }
}
