<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
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

    protected function countQueries(callable $callback): int
    {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });

        $callback();

        DB::flushQueryLog();

        return $count;
    }

    public function test_repeated_catalog_listing_requests_hit_the_database_once(): void
    {
        $this->makeProduct(['name' => 'Cached Product']);

        $this->getJson('/api/products')->assertOk();

        $secondCallQueries = $this->countQueries(function () {
            $this->getJson('/api/products')->assertOk();
        });

        $this->assertSame(0, $secondCallQueries);
    }

    public function test_updating_a_product_invalidates_the_catalog_listing_cache(): void
    {
        $product = $this->makeProduct(['name' => 'Original Name']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Original Name');

        $product->update(['name' => 'Updated Name']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Updated Name');
    }

    public function test_repeated_product_detail_requests_hit_the_database_once(): void
    {
        $product = $this->makeProduct();
        $product->variants()->create([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ]);

        $this->getJson("/api/products/{$product->slug}")->assertOk();

        $secondCallQueries = $this->countQueries(function () use ($product) {
            $this->getJson("/api/products/{$product->slug}")->assertOk();
        });

        // Route model binding still resolves the product itself; the design
        // and variants relations should come from cache instead of re-querying.
        $this->assertSame(1, $secondCallQueries);
    }

    public function test_decrementing_variant_stock_invalidates_the_product_detail_cache(): void
    {
        $product = $this->makeProduct();
        $variant = $product->variants()->create([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.variants.0.stock_quantity', 10);

        $variant->decrement('stock_quantity', 3);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.variants.0.stock_quantity', 7);
    }

    public function test_a_new_review_invalidates_the_product_detail_cache(): void
    {
        $product = $this->makeProduct();

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.average_rating', null)
            ->assertJsonPath('data.reviews_count', 0);

        $user = User::factory()->create();
        $address = $user->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Buyer',
            'line1' => '1 Test St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);
        $order = $user->orders()->create([
            'order_number' => 'ORD-'.uniqid(),
            'subtotal' => 30,
            'total_amount' => 30,
            'shipping_address_id' => $address->id,
            'billing_address_id' => $address->id,
            'payment_status' => 'paid',
        ]);
        Review::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'order_id' => $order->id,
            'rating' => 5,
        ]);

        $this->getJson("/api/products/{$product->slug}")
            ->assertOk()
            ->assertJsonPath('data.average_rating', 5)
            ->assertJsonPath('data.reviews_count', 1);
    }
}
