<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(array $overrides = []): Product
    {
        $design = Design::create([
            'title' => 'Test Design',
            'category' => 'cultural-signal',
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

    public function test_a_guest_cannot_view_the_wishlist(): void
    {
        $this->getJson('/api/wishlist')->assertUnauthorized();
    }

    public function test_a_guest_cannot_add_a_product_to_the_wishlist(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->slug}/wishlist")->assertUnauthorized();
    }

    public function test_a_user_can_add_a_product_to_their_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->postJson("/api/products/{$product->slug}/wishlist");

        $response->assertCreated()
            ->assertJsonPath('data.product.id', $product->id);

        $this->assertDatabaseHas('wishlist_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_a_user_can_list_their_wishlist(): void
    {
        $user = User::factory()->create();
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);

        $this->actingAs($user)->postJson("/api/products/{$productA->slug}/wishlist")->assertCreated();
        $this->actingAs($user)->postJson("/api/products/{$productB->slug}/wishlist")->assertCreated();

        $response = $this->actingAs($user)->getJson('/api/wishlist');

        $response->assertOk()->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('product.name');
        $this->assertTrue($names->contains('Product A'));
        $this->assertTrue($names->contains('Product B'));
    }

    public function test_the_wishlist_only_shows_the_current_users_items(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($userA)->postJson("/api/products/{$product->slug}/wishlist")->assertCreated();

        $response = $this->actingAs($userB)->getJson('/api/wishlist');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_a_product_cannot_be_wishlisted_twice_by_the_same_user(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/wishlist")->assertCreated();
        $this->actingAs($user)->postJson("/api/products/{$product->slug}/wishlist")->assertOk();

        $this->assertDatabaseCount('wishlist_items', 1);
    }

    public function test_a_user_can_remove_a_product_from_their_wishlist(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/wishlist")->assertCreated();

        $response = $this->actingAs($user)->deleteJson("/api/products/{$product->slug}/wishlist");

        $response->assertNoContent();
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_removing_a_product_never_wishlisted_is_a_no_op(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->deleteJson("/api/products/{$product->slug}/wishlist");

        $response->assertNoContent();
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_a_guest_cannot_remove_a_product_from_a_wishlist(): void
    {
        $product = $this->makeProduct();

        $this->deleteJson("/api/products/{$product->slug}/wishlist")->assertUnauthorized();
    }
}
