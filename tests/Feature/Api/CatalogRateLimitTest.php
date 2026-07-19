<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CatalogRateLimitTest extends TestCase
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

    protected function setUp(): void
    {
        parent::setUp();

        // The 'catalog-read' limiter is keyed by IP; clear it between tests so
        // one test's hits don't bleed into the next (they share the same
        // testing-client IP by default).
        RateLimiter::clear('catalog-read:127.0.0.1');
    }

    public function test_normal_volume_browsing_of_the_catalog_is_never_throttled(): void
    {
        $product = $this->makeProduct();

        // A shopper paginating, re-sorting, and viewing a product plus its
        // reviews well within a minute — comfortably under the 60/min cap.
        for ($i = 0; $i < 10; $i++) {
            $this->getJson('/api/products')->assertOk();
        }

        $this->getJson("/api/products/{$product->slug}")->assertOk();
        $this->getJson("/api/products/{$product->slug}/reviews")->assertOk();
    }

    public function test_hammering_the_catalog_endpoint_past_the_per_ip_limit_returns_429(): void
    {
        $this->makeProduct();

        for ($i = 0; $i < 60; $i++) {
            $response = $this->getJson('/api/products');
            $response->assertOk();
        }

        // The 61st request within the same minute, from the same IP, exceeds
        // the 'catalog-read' limiter and must be rejected.
        $this->getJson('/api/products')->assertStatus(429);
    }

    public function test_the_rate_limit_is_shared_across_the_public_catalog_routes(): void
    {
        $product = $this->makeProduct();

        for ($i = 0; $i < 60; $i++) {
            $this->getJson('/api/products')->assertOk();
        }

        // Product detail and reviews use the same 'catalog-read' limiter, so
        // the exhausted quota blocks them too, not just the listing route.
        $this->getJson("/api/products/{$product->slug}")->assertStatus(429);
        $this->getJson("/api/products/{$product->slug}/reviews")->assertStatus(429);
    }
}
