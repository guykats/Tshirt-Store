<?php

namespace Tests\Feature;

use App\Models\Design;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
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

    public function test_sitemap_is_valid_xml_with_the_correct_content_type(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'Sitemap response is not valid XML.');
    }

    public function test_sitemap_includes_static_pages(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString(url('/'), $content);
        $this->assertStringContainsString(url('/about'), $content);
    }

    public function test_sitemap_includes_active_products_but_not_draft_or_archived_ones(): void
    {
        $active = $this->makeProduct(['name' => 'Active Product', 'status' => 'active']);
        $draft = $this->makeProduct(['name' => 'Draft Product', 'status' => 'draft']);
        $archived = $this->makeProduct(['name' => 'Archived Product', 'status' => 'archived']);

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString(url('/products/'.$active->slug), $content);
        $this->assertStringNotContainsString(url('/products/'.$draft->slug), $content);
        $this->assertStringNotContainsString(url('/products/'.$archived->slug), $content);
    }
}
