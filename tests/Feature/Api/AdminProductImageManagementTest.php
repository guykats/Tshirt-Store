<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $design = Design::create([
            'title' => 'Test Design',
            'category' => 'cultural-signal',
            'mockup_url' => 'chai',
            'status' => 'approved',
        ]);

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

    protected function makeImage(Product $product, array $overrides = []): ProductImage
    {
        return $product->images()->create(array_merge([
            'url' => 'star-of-david',
            'alt_text' => 'Front view',
            'position' => 0,
        ], $overrides));
    }

    protected function imagePayload(array $overrides = []): array
    {
        return array_merge([
            'url' => 'menorah',
            'alt_text' => 'Back view',
            'color' => 'Black',
        ], $overrides);
    }

    public function test_guests_cannot_manage_product_images(): void
    {
        $product = $this->makeProduct();
        $image = $this->makeImage($product);

        $this->postJson("/api/admin/products/{$product->slug}/images", $this->imagePayload())->assertUnauthorized();
        $this->putJson("/api/admin/products/{$product->slug}/images/{$image->id}", $this->imagePayload())->assertUnauthorized();
        $this->patchJson("/api/admin/products/{$product->slug}/images/reorder", ['image_ids' => [$image->id]])->assertUnauthorized();
        $this->deleteJson("/api/admin/products/{$product->slug}/images/{$image->id}")->assertUnauthorized();
    }

    public function test_customers_cannot_manage_product_images(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $image = $this->makeImage($product);

        $this->actingAs($customer)->postJson("/api/admin/products/{$product->slug}/images", $this->imagePayload())->assertForbidden();
        $this->actingAs($customer)->putJson("/api/admin/products/{$product->slug}/images/{$image->id}", $this->imagePayload())->assertForbidden();
        $this->actingAs($customer)->patchJson("/api/admin/products/{$product->slug}/images/reorder", ['image_ids' => [$image->id]])->assertForbidden();
        $this->actingAs($customer)->deleteJson("/api/admin/products/{$product->slug}/images/{$image->id}")->assertForbidden();
    }

    public function test_an_admin_can_add_and_update_a_product_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $createResponse = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/images",
            $this->imagePayload()
        );
        $createResponse->assertCreated()
            ->assertJsonPath('data.url', 'menorah')
            ->assertJsonPath('data.color', 'Black')
            ->assertJsonPath('data.position', 0);

        $imageId = $createResponse->json('data.id');

        $updateResponse = $this->actingAs($admin)->putJson(
            "/api/admin/products/{$product->slug}/images/{$imageId}",
            $this->imagePayload(['alt_text' => 'Updated alt text'])
        );
        $updateResponse->assertOk()->assertJsonPath('data.alt_text', 'Updated alt text');

        $this->assertDatabaseHas('product_images', ['id' => $imageId, 'alt_text' => 'Updated alt text']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_image.created']);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_image.updated']);
    }

    public function test_new_images_are_appended_after_existing_ones(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $this->makeImage($product, ['position' => 0]);
        $this->makeImage($product, ['position' => 1]);

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/images",
            $this->imagePayload()
        );

        $response->assertCreated()->assertJsonPath('data.position', 2);
    }

    public function test_creating_an_image_requires_a_url(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->postJson(
            "/api/admin/products/{$product->slug}/images",
            $this->imagePayload(['url' => ''])
        );

        $response->assertStatus(422)->assertJsonValidationErrors('url');
    }

    public function test_an_admin_can_reorder_a_products_images(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $first = $this->makeImage($product, ['url' => 'first', 'position' => 0]);
        $second = $this->makeImage($product, ['url' => 'second', 'position' => 1]);
        $third = $this->makeImage($product, ['url' => 'third', 'position' => 2]);

        $response = $this->actingAs($admin)->patchJson(
            "/api/admin/products/{$product->slug}/images/reorder",
            ['image_ids' => [$third->id, $first->id, $second->id]]
        );

        $response->assertOk();

        $this->assertDatabaseHas('product_images', ['id' => $third->id, 'position' => 0]);
        $this->assertDatabaseHas('product_images', ['id' => $first->id, 'position' => 1]);
        $this->assertDatabaseHas('product_images', ['id' => $second->id, 'position' => 2]);

        $orderedIds = $product->images()->pluck('id')->all();
        $this->assertSame([$third->id, $first->id, $second->id], $orderedIds);
    }

    public function test_reordering_rejects_a_mismatched_set_of_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $image = $this->makeImage($product);
        $otherProductImage = $this->makeImage($this->makeProduct());

        $response = $this->actingAs($admin)->patchJson(
            "/api/admin/products/{$product->slug}/images/reorder",
            ['image_ids' => [$image->id, $otherProductImage->id]]
        );

        $response->assertStatus(422);
    }

    public function test_an_admin_can_delete_a_product_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $image = $this->makeImage($product);

        $this->actingAs($admin)->deleteJson("/api/admin/products/{$product->slug}/images/{$image->id}")->assertOk();

        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'product_image.deleted']);
    }

    public function test_an_image_belonging_to_a_different_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);
        $image = $this->makeImage($productB);

        $this->actingAs($admin)
            ->putJson("/api/admin/products/{$productA->slug}/images/{$image->id}", $this->imagePayload())
            ->assertNotFound();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/products/{$productA->slug}/images/{$image->id}")
            ->assertNotFound();
    }

    public function test_managing_images_for_a_nonexistent_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson('/api/admin/products/no-such-product/images', $this->imagePayload())
            ->assertNotFound();
    }

    public function test_the_public_product_resource_exposes_images_in_position_order(): void
    {
        $product = $this->makeProduct(['status' => 'active']);
        $second = $this->makeImage($product, ['url' => 'second', 'position' => 1]);
        $first = $this->makeImage($product, ['url' => 'first', 'position' => 0]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertOk();
        $urls = collect($response->json('data.images'))->pluck('url')->all();
        $this->assertSame(['first', 'second'], $urls);
    }
}
