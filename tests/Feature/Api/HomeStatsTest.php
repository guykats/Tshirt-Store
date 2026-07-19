<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(): Product
    {
        $design = Design::create(['title' => 'Test Design', 'status' => 'approved']);

        return Product::create([
            'design_id' => $design->id,
            'name' => 'Test Tee',
            'slug' => 'test-tee-'.uniqid(),
            'base_price' => 30,
            'sku' => 'TT-'.uniqid(),
            'status' => 'active',
        ]);
    }

    protected function makeVariant(Product $product): ProductVariant
    {
        return $product->variants()->create([
            'size' => 'M', 'color' => 'Black', 'sku' => 'TT-M-'.uniqid(), 'stock_quantity' => 10,
        ]);
    }

    protected function address(User $user, string $country = 'US'): Address
    {
        return $user->addresses()->create([
            'type' => 'shipping', 'full_name' => 'Test Buyer', 'line1' => '1 Test St',
            'city' => 'New York', 'state' => 'NY', 'postal_code' => '10001', 'country' => $country,
        ]);
    }

    protected function makeOrder(User $user, array $overrides = []): Order
    {
        $address = $this->address($user, $overrides['country'] ?? 'US');
        unset($overrides['country']);

        return $user->orders()->create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'payment_status' => 'unpaid',
        ], $overrides));
    }

    public function test_home_stats_are_zero_and_rating_is_null_with_no_data(): void
    {
        $response = $this->getJson('/api/home-stats');

        $response->assertOk()
            ->assertJsonPath('data.completed_orders', 0)
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.review_count', 0)
            ->assertJsonPath('data.countries_served', 0);
    }

    public function test_completed_orders_only_counts_paid_orders(): void
    {
        $user = User::factory()->create();
        $this->makeOrder($user, ['payment_status' => 'paid']);
        $this->makeOrder($user, ['payment_status' => 'paid']);
        $this->makeOrder($user, ['payment_status' => 'unpaid']);

        $response = $this->getJson('/api/home-stats');

        $response->assertOk()->assertJsonPath('data.completed_orders', 2);
    }

    public function test_average_rating_reflects_real_reviews(): void
    {
        $product = $this->makeProduct();
        $variant = $this->makeVariant($product);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $orderA = $this->makeOrder($userA, ['payment_status' => 'paid']);
        $orderA->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 30, 'subtotal' => 30]);
        $orderB = $this->makeOrder($userB, ['payment_status' => 'paid']);
        $orderB->items()->create(['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => 30, 'subtotal' => 30]);

        Review::create(['product_id' => $product->id, 'user_id' => $userA->id, 'order_id' => $orderA->id, 'rating' => 5]);
        Review::create(['product_id' => $product->id, 'user_id' => $userB->id, 'order_id' => $orderB->id, 'rating' => 4]);

        $response = $this->getJson('/api/home-stats');

        $response->assertOk()
            ->assertJsonPath('data.average_rating', 4.5)
            ->assertJsonPath('data.review_count', 2);
    }

    public function test_countries_served_counts_distinct_shipping_countries_on_paid_orders_only(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $userC = User::factory()->create();
        $this->makeOrder($userA, ['payment_status' => 'paid', 'country' => 'US']);
        $this->makeOrder($userB, ['payment_status' => 'paid', 'country' => 'IL']);
        // Same country as an existing paid order — should not double count.
        $this->makeOrder($userC, ['payment_status' => 'paid', 'country' => 'US']);
        // Unpaid order to a new country — should not count at all.
        $this->makeOrder($userA, ['payment_status' => 'unpaid', 'country' => 'GB']);

        $response = $this->getJson('/api/home-stats');

        $response->assertOk()->assertJsonPath('data.countries_served', 2);
    }
}
