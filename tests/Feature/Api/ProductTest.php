<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
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

    public function test_the_catalog_only_lists_active_products(): void
    {
        $this->makeProduct(['name' => 'Active Product', 'status' => 'active']);
        $this->makeProduct(['name' => 'Draft Product', 'status' => 'draft']);
        $this->makeProduct(['name' => 'Archived Product', 'status' => 'archived']);

        $response = $this->getJson('/api/products');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Active Product'));
        $this->assertFalse($names->contains('Draft Product'));
        $this->assertFalse($names->contains('Archived Product'));
    }

    public function test_show_returns_a_product_with_design_and_variants(): void
    {
        $product = $this->makeProduct();
        $product->variants()->create([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TT-M-BLK-'.uniqid(),
            'stock_quantity' => 10,
        ]);

        $response = $this->getJson("/api/products/{$product->slug}");

        $response->assertOk()
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.design.title', 'Test Design')
            ->assertJsonCount(1, 'data.variants');
    }

    public function test_show_returns_404_for_an_unknown_slug(): void
    {
        $this->getJson('/api/products/does-not-exist')->assertNotFound();
    }

    public function test_show_returns_404_for_a_draft_product(): void
    {
        $product = $this->makeProduct(['status' => 'draft']);

        $this->getJson("/api/products/{$product->slug}")->assertNotFound();
    }

    public function test_show_returns_404_for_an_archived_product(): void
    {
        $product = $this->makeProduct(['status' => 'archived']);

        $this->getJson("/api/products/{$product->slug}")->assertNotFound();
    }

    public function test_products_are_publicly_accessible_without_authentication(): void
    {
        $this->makeProduct();

        $this->getJson('/api/products')->assertOk();
    }
}
