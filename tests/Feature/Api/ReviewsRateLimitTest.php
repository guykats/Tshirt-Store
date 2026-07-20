<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ReviewsRateLimitTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        // The 'reviews' limiter is keyed by user id; clear it between tests so
        // one test's hits don't bleed into the next (user ids can collide
        // across tests since each test starts a fresh migrated database).
        RateLimiter::clear('reviews:1');
    }

    public function test_a_user_retrying_a_rejected_review_a_few_times_is_never_throttled(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        // First request succeeds; the rest fail with a 422 (duplicate review)
        // but still consume the same 'reviews' limiter bucket - comfortably
        // under the 10/min cap.
        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertCreated();

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
                ->assertStatus(422);
        }
    }

    public function test_hammering_review_submission_past_the_per_user_limit_returns_429(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($user, $variant);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5]);
        }

        // The 11th request within the same minute, from the same user, exceeds
        // the 'reviews' limiter and must be rejected before the controller
        // (and its proof-of-purchase / duplicate checks) ever runs.
        $this->actingAs($user)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertStatus(429);
    }

    public function test_the_rate_limit_is_shared_with_review_deletion_for_the_same_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $reviewer = User::factory()->create();
        $order = $this->makePaidOrder($reviewer, $variant);
        $review = Review::create([
            'product_id' => $product->id,
            'user_id' => $reviewer->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);

        // Exhaust the admin's 'reviews' bucket via repeated (failing) store
        // attempts - the admin has not purchased the product, so each is a 403,
        // but that still consumes the same per-user limiter bucket used by
        // destroy.
        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($admin)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5]);
        }

        $this->actingAs($admin)
            ->deleteJson("/api/products/{$product->slug}/reviews/{$review->id}")
            ->assertStatus(429);

        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_different_users_have_independent_rate_limit_buckets(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $this->makePaidOrder($userA, $variant);
        $this->makePaidOrder($userB, $variant);

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($userA)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5]);
        }
        $this->actingAs($userA)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertStatus(429);

        // A different user is unaffected by userA's exhausted bucket.
        $this->actingAs($userB)->postJson("/api/products/{$product->slug}/reviews", ['rating' => 5])
            ->assertCreated();
    }
}
