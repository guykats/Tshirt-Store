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

    public function test_the_catalog_exposes_pagination_metadata_the_frontend_depends_on(): void
    {
        foreach (range(1, 25) as $i) {
            $this->makeProduct(['name' => "Product {$i}"]);
        }

        $page1 = $this->getJson('/api/products');
        $page1->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 25);

        $page2 = $this->getJson('/api/products?page=2');
        $page2->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('meta.current_page', 2);
    }

    public function test_search_matches_products_by_name_case_insensitively(): void
    {
        $this->makeProduct(['name' => 'Star of David Tee']);
        $this->makeProduct(['name' => 'Menorah Hoodie']);

        $response = $this->getJson('/api/products?search=star');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Star of David Tee'));
        $this->assertFalse($names->contains('Menorah Hoodie'));
    }

    public function test_search_matches_products_by_description(): void
    {
        $this->makeProduct(['name' => 'Chai Tee', 'description' => 'A minimalist chai symbol design.']);
        $this->makeProduct(['name' => 'Hamsa Tee', 'description' => 'A protective hand motif.']);

        $response = $this->getJson('/api/products?search=minimalist');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Chai Tee'));
        $this->assertFalse($names->contains('Hamsa Tee'));
    }

    public function test_search_with_no_matches_returns_an_empty_result_set(): void
    {
        $this->makeProduct(['name' => 'Star of David Tee']);

        $response = $this->getJson('/api/products?search=nonexistent-search-term');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    public function test_sort_by_price_ascending_reorders_results(): void
    {
        $this->makeProduct(['name' => 'Expensive Tee', 'base_price' => 90.00]);
        $this->makeProduct(['name' => 'Cheap Tee', 'base_price' => 10.00]);
        $this->makeProduct(['name' => 'Mid Tee', 'base_price' => 50.00]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertSame(['Cheap Tee', 'Mid Tee', 'Expensive Tee'], $names);
    }

    public function test_sort_by_price_descending_reorders_results(): void
    {
        $this->makeProduct(['name' => 'Expensive Tee', 'base_price' => 90.00]);
        $this->makeProduct(['name' => 'Cheap Tee', 'base_price' => 10.00]);
        $this->makeProduct(['name' => 'Mid Tee', 'base_price' => 50.00]);

        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name')->values()->all();
        $this->assertSame(['Expensive Tee', 'Mid Tee', 'Cheap Tee'], $names);
    }
}
