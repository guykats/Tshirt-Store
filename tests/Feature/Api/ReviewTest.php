<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    protected function makeVariant(Product $product, array $overrides = []): ProductVariant
    {
        return $product->variants()->create(array_merge([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ], $overrides));
    }

    protected function address(User $user): \App\Models\Address
    {
        return $user->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);
    }

    /**
     * Creates a paid order for the given user containing the given variant —
     * the proof-of-purchase a review requires.
     */
    protected function makePaidOrder(User $user, ProductVariant $variant, array $overrides = []): Order
    {
        $address = $this->address($user);

        $order = $user->orders()->create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'payment_status' => 'paid',
        ], $overrides));

        $order->items()->create([
            'product_variant_id' => $variant->id,
            'quantity' => 1,
            'unit_price' => 30,
            'subtotal' => 30,
        ]);

        return $order;
    }

    public function test_a_user_who_purchased_the_product_can_leave_a_review(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
            'body' => 'Great shirt, fits perfectly.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.body', 'Great shirt, fits perfectly.')
            ->assertJsonPath('data.reviewer_name', $user->name);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);
    }

    public function test_a_review_body_is_optional(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 4,
        ])->assertCreated()->assertJsonPath('data.body', null);
    }

    public function test_a_user_who_never_purchased_the_product_cannot_review_it(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->makeVariant($product);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_user_whose_order_is_unpaid_cannot_review_the_product(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant, ['payment_status' => 'unpaid']);

        $response = $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_user_cannot_review_a_product_they_purchased_but_a_different_one_from_the_same_order(): void
    {
        $user = User::factory()->create();
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);
        $variantA = $this->makeVariant($productA);
        $this->makeVariant($productB);
        $this->makePaidOrder($user, $variantA);

        $response = $this->actingAs($user)->postJson("/api/products/{$productB->slug}/reviews", [
            'rating' => 3,
        ]);

        $response->assertForbidden();
    }

    public function test_a_user_cannot_review_the_same_product_twice(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();

        $response = $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_a_guest_cannot_leave_a_review(): void
    {
        $product = $this->makeProduct();

        $this->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertUnauthorized();
    }

    public function test_rating_must_be_between_one_and_five(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');
    }

    public function test_reviews_index_is_public_and_lists_reviews_with_average_rating(): void
    {
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $userA = User::factory()->create(['name' => 'Alice']);
        $this->makePaidOrder($userA, $variant);
        $this->actingAs($userA)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 5,
            'body' => 'Loved it.',
        ])->assertCreated();

        $userB = User::factory()->create(['name' => 'Bob']);
        $this->makePaidOrder($userB, $variant);
        $this->actingAs($userB)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 4,
        ])->assertCreated();

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.count', 2)
            ->assertJsonPath('meta.average_rating', 4.5);

        $names = collect($response->json('data'))->pluck('reviewer_name');
        $this->assertTrue($names->contains('Alice'));
        $this->assertTrue($names->contains('Bob'));
    }

    public function test_reviews_index_for_a_product_with_no_reviews_returns_an_empty_list(): void
    {
        $product = $this->makeProduct();

        $response = $this->getJson("/api/products/{$product->slug}/reviews");

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.count', 0)
            ->assertJsonPath('meta.average_rating', null);
    }

    public function test_eligibility_endpoint_reflects_purchase_and_review_state(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $this->actingAs($user)->getJson("/api/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('can_review', false)
            ->assertJsonPath('has_purchased', false)
            ->assertJsonPath('already_reviewed', false);

        $this->makePaidOrder($user, $variant);

        $this->actingAs($user)->getJson("/api/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('can_review', true)
            ->assertJsonPath('has_purchased', true)
            ->assertJsonPath('already_reviewed', false);

        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();

        $this->actingAs($user)->getJson("/api/products/{$product->slug}/reviews/eligibility")
            ->assertOk()
            ->assertJsonPath('can_review', false)
            ->assertJsonPath('has_purchased', true)
            ->assertJsonPath('already_reviewed', true);
    }

    public function test_eligibility_endpoint_requires_authentication(): void
    {
        $product = $this->makeProduct();

        $this->getJson("/api/products/{$product->slug}/reviews/eligibility")->assertUnauthorized();
    }
}
