<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function makeVariant(array $overrides = [])
    {
        $design = Design::create(['title' => 'Test Design '.uniqid(), 'status' => 'approved']);
        $product = Product::create([
            'design_id' => $design->id,
            'name' => 'Test Product '.uniqid(),
            'slug' => 'test-product-'.uniqid(),
            'base_price' => 30,
            'sku' => 'TP-'.uniqid(),
            'status' => 'active',
        ]);

        return $product->variants()->create(array_merge([
            'size' => 'M',
            'color' => 'Black',
            'sku' => 'TP-M-'.uniqid(),
            'stock_quantity' => 3,
        ], $overrides));
    }

    public function test_an_admin_sees_variants_at_or_below_the_default_threshold(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $low = $this->makeVariant(['stock_quantity' => 3]);
        $atThreshold = $this->makeVariant(['stock_quantity' => 5]);
        $this->makeVariant(['stock_quantity' => 20]);

        $response = $this->actingAs($admin)->getJson('/api/inventory/low-stock');

        $response->assertOk()->assertJsonCount(2, 'data');
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($low->id));
        $this->assertTrue($ids->contains($atThreshold->id));
    }

    public function test_variants_above_the_threshold_are_excluded(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->makeVariant(['stock_quantity' => 50]);

        $response = $this->actingAs($admin)->getJson('/api/inventory/low-stock');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_the_threshold_can_be_narrowed_via_query_param(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $veryLow = $this->makeVariant(['stock_quantity' => 1]);
        $this->makeVariant(['stock_quantity' => 4]);

        $response = $this->actingAs($admin)->getJson('/api/inventory/low-stock?threshold=2');

        $response->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame($veryLow->id, $response->json('data.0.id'));
    }

    public function test_results_include_the_product_name_and_variant_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $variant = $this->makeVariant(['stock_quantity' => 2, 'size' => 'L', 'color' => 'Navy']);

        $response = $this->actingAs($admin)->getJson('/api/inventory/low-stock');

        $response->assertOk()
            ->assertJsonPath('data.0.product.name', $variant->product->name)
            ->assertJsonPath('data.0.size', 'L')
            ->assertJsonPath('data.0.color', 'Navy')
            ->assertJsonPath('data.0.stock_quantity', 2);
    }

    public function test_a_non_admin_customer_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $this->makeVariant(['stock_quantity' => 1]);

        $this->actingAs($customer)->getJson('/api/inventory/low-stock')->assertForbidden();
    }

    public function test_a_guest_is_unauthenticated(): void
    {
        $this->makeVariant(['stock_quantity' => 1]);

        $this->getJson('/api/inventory/low-stock')->assertUnauthorized();
    }
}
