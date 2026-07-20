<?php

namespace Tests\Feature;

use App\Models\Design;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpaOgTagsTest extends TestCase
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
            'name' => 'Aleph Tee',
            'slug' => 'aleph-tee-'.uniqid(),
            'description' => 'A quiet nod to the first letter.',
            'base_price' => 32.00,
            'sku' => 'AT-'.uniqid(),
            'status' => 'active',
        ], $overrides));
    }

    public function test_homepage_renders_the_site_level_default_og_tags(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('og:title" content="Jewish Identity, Understated', $content);
        $this->assertStringContainsString(url('/og-image.png'), $content);
    }

    public function test_product_detail_url_renders_that_products_own_og_tags(): void
    {
        $product = $this->makeProduct();

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('og:title" content="'.$product->name, $content);
        $this->assertStringContainsString('og:description" content="'.$product->description, $content);
        $this->assertStringContainsString(url()->current(), $content);
        $this->assertStringNotContainsString('Jewish Identity, Understated', $content);
    }

    public function test_product_with_a_real_gallery_image_url_uses_it_as_the_og_image(): void
    {
        $product = $this->makeProduct();
        ProductImage::create([
            'product_id' => $product->id,
            'url' => 'https://cdn.example.com/aleph-tee-front.jpg',
            'alt_text' => 'Aleph tee, front',
            'position' => 0,
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $this->assertStringContainsString(
            'og:image" content="https://cdn.example.com/aleph-tee-front.jpg"',
            $response->getContent(),
        );
    }

    public function test_product_with_only_a_motif_keyword_image_falls_back_to_the_brand_og_image(): void
    {
        $product = $this->makeProduct();
        ProductImage::create([
            'product_id' => $product->id,
            'url' => 'aleph',
            'alt_text' => 'Aleph motif',
            'position' => 0,
        ]);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $this->assertStringContainsString('og:image" content="'.url('/og-image.png'), $response->getContent());
    }

    public function test_unknown_product_slug_falls_back_to_the_site_level_default_og_tags(): void
    {
        $response = $this->get('/products/this-slug-does-not-exist');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('og:title" content="Jewish Identity, Understated', $content);
        $this->assertStringContainsString(url('/og-image.png'), $content);
    }

    public function test_draft_product_is_not_exposed_via_og_tags_and_falls_back_to_site_defaults(): void
    {
        $product = $this->makeProduct(['status' => 'draft']);

        $response = $this->get('/products/'.$product->slug);

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringNotContainsString('og:title" content="'.$product->name, $content);
        $this->assertStringContainsString('og:title" content="Jewish Identity, Understated', $content);
    }

    public function test_a_non_product_route_still_renders_site_level_default_og_tags(): void
    {
        $response = $this->get('/about');

        $response->assertOk();
        $this->assertStringContainsString('og:title" content="Jewish Identity, Understated', $response->getContent());
    }
}
