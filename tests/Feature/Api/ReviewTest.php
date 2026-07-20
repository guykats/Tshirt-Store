<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
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

    public function test_a_non_admin_cannot_delete_a_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();
        $review = Review::first();

        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherCustomer)
            ->deleteJson("/api/products/{$product->slug}/reviews/{$review->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_a_review_authors_own_review_is_flagged_is_own_and_others_are_not(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();
        $review = Review::first();

        $this->actingAs($reviewer)
            ->getJson("/api/products/{$product->slug}/reviews")
            ->assertJsonPath('data.0.is_own', true);

        $otherCustomer = User::factory()->create();
        $this->actingAs($otherCustomer)
            ->getJson("/api/products/{$product->slug}/reviews")
            ->assertJsonPath('data.0.is_own', false);

        $this->getJson("/api/products/{$product->slug}/reviews")
            ->assertJsonPath('data.0.is_own', false);
    }

    public function test_a_review_author_can_edit_their_own_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", [
            'rating' => 3,
            'body' => 'It was okay.',
        ])->assertCreated();
        $review = Review::first();

        $response = $this->actingAs($reviewer)->patchJson("/api/products/{$product->slug}/reviews/{$review->id}", [
            'rating' => 5,
            'body' => 'Actually, it grew on me.',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.body', 'Actually, it grew on me.');

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'body' => 'Actually, it grew on me.',
        ]);
    }

    public function test_a_non_owner_cannot_edit_someone_elses_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3])->assertCreated();
        $review = Review::first();

        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($otherCustomer)
            ->patchJson("/api/products/{$product->slug}/reviews/{$review->id}", ['rating' => 1])
            ->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3]);
    }

    public function test_an_admin_cannot_edit_someone_elses_review_via_the_self_service_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3])->assertCreated();
        $review = Review::first();

        $this->actingAs($admin)
            ->patchJson("/api/products/{$product->slug}/reviews/{$review->id}", ['rating' => 1])
            ->assertForbidden();
    }

    public function test_a_guest_cannot_edit_a_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $order = $this->makePaidOrder($reviewer, $variant);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);

        $this->patchJson("/api/products/{$product->slug}/reviews/{$review->id}", ['rating' => 1])
            ->assertUnauthorized();
    }

    public function test_editing_a_review_validates_rating(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 3])->assertCreated();
        $review = Review::first();

        $this->actingAs($reviewer)
            ->patchJson("/api/products/{$product->slug}/reviews/{$review->id}", ['rating' => 6])
            ->assertUnprocessable()->assertJsonValidationErrors('rating');
    }

    public function test_editing_a_review_belonging_to_a_different_product_returns_404(): void
    {
        $reviewer = User::factory()->create();
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);
        $variantB = $this->makeVariant($productB);
        $this->makePaidOrder($reviewer, $variantB);
        $this->actingAs($reviewer)->postJson("/api/products/{$productB->slug}/reviews", ['rating' => 4])->assertCreated();
        $review = Review::first();

        $this->actingAs($reviewer)
            ->patchJson("/api/products/{$productA->slug}/reviews/{$review->id}", ['rating' => 2])
            ->assertNotFound();
    }

    public function test_a_review_author_can_delete_their_own_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();
        $review = Review::first();

        $this->actingAs($reviewer)
            ->deleteJson("/api/products/{$product->slug}/reviews/{$review->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_a_review_author_can_delete_then_resubmit_a_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($reviewer, $variant);
        $this->actingAs($reviewer)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 2])->assertCreated();
        $review = Review::first();

        $this->actingAs($reviewer)
            ->deleteJson("/api/products/{$product->slug}/reviews/{$review->id}")
            ->assertOk();

        $this->actingAs($reviewer)
            ->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertCreated();

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', ['user_id' => $reviewer->id, 'rating' => 5]);
    }

    public function test_a_guest_cannot_delete_a_review(): void
    {
        $reviewer = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $order = $this->makePaidOrder($reviewer, $variant);
        // Created directly (not via actingAs()->postJson()) so this request truly
        // has no authenticated actor — actingAs() would otherwise persist across
        // the rest of this test method's requests.
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);

        $this->deleteJson("/api/products/{$product->slug}/reviews/{$review->id}")->assertUnauthorized();
    }

    public function test_an_admin_can_delete_a_review_and_the_average_rating_is_recalculated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $userA = User::factory()->create(['name' => 'Alice']);
        $this->makePaidOrder($userA, $variant);
        $this->actingAs($userA)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])->assertCreated();

        $userB = User::factory()->create(['name' => 'Bob']);
        $this->makePaidOrder($userB, $variant);
        $this->actingAs($userB)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 1])->assertCreated();

        // round((5+1)/2, 1) is a whole number, which json_encode()s as an int (3),
        // not a float (3.0) — assert against the int so this matches what the
        // response actually contains rather than a strict-typed literal.
        $this->getJson("/api/products/{$product->slug}/reviews")->assertJsonPath('meta.average_rating', 3);

        $badReview = Review::where('user_id', $userB->id)->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->slug}/reviews/{$badReview->id}")
            ->assertOk();

        $this->assertDatabaseMissing('reviews', ['id' => $badReview->id]);
        $this->assertDatabaseHas('system_events', ['event_type' => 'review.deleted']);

        $this->getJson("/api/products/{$product->slug}/reviews")
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.average_rating', 5);
    }

    public function test_deleting_a_nonexistent_review_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->slug}/reviews/999999")
            ->assertNotFound();
    }

    public function test_deleting_a_review_belonging_to_a_different_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $productA = $this->makeProduct(['name' => 'Product A']);
        $productB = $this->makeProduct(['name' => 'Product B']);
        $variantB = $this->makeVariant($productB);

        $reviewer = User::factory()->create();
        $this->makePaidOrder($reviewer, $variantB);
        $this->actingAs($reviewer)->postJson("/api/products/{$productB->slug}/reviews", ['rating' => 4])->assertCreated();
        $review = Review::first();

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$productA->slug}/reviews/{$review->id}")
            ->assertNotFound();
    }

    public function test_the_admin_manage_endpoint_lists_reviews_across_products_and_is_admin_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);

        $reviewer = User::factory()->create(['name' => 'Carol']);
        $order = $this->makePaidOrder($reviewer, $variant);
        // Created directly rather than via actingAs()->postJson() so the guest
        // assertion right below genuinely has no lingering authenticated actor —
        // actingAs() otherwise persists across the rest of this test's requests.
        Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'order_id' => $order->id,
            'rating' => 5,
            'body' => 'Excellent.',
        ]);

        $this->getJson('/api/admin/reviews')->assertUnauthorized();
        $this->actingAs($customer)->getJson('/api/admin/reviews')->assertForbidden();

        $response = $this->actingAs($admin)->getJson('/api/admin/reviews');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reviewer_name', 'Carol')
            ->assertJsonPath('data.0.product_name', $product->name);
    }
}
