<?php

namespace Database\Seeders;

use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@tshirt-store.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@tshirt-store.test',
            'password' => 'password',
        ]);

        $approvedDesign = Design::create([
            'title' => 'Minimal Star of David',
            'category' => 'cultural-signal',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        $product = Product::create([
            'design_id' => $approvedDesign->id,
            'name' => 'Minimal Star Tee',
            'slug' => 'minimal-star-tee',
            'description' => 'A clean, understated take on a timeless cultural symbol.',
            'base_price' => 34.00,
            'sku' => 'MST-001',
            'status' => 'active',
        ]);

        foreach (['S', 'M', 'L', 'XL'] as $size) {
            $product->variants()->create([
                'size' => $size,
                'color' => 'Black',
                'sku' => "MST-001-{$size}-BLK",
                'stock_quantity' => 25,
            ]);
        }

        Design::create([
            'title' => 'Hebrew Script Streetwear',
            'category' => 'cultural-signal',
            'status' => 'pending_approval',
        ]);

        Design::create([
            'title' => 'Menorah Line Art',
            'category' => 'cultural-signal',
            'status' => 'pending_approval',
        ]);

        $shipping = $customer->addresses()->create([
            'type' => 'shipping',
            'full_name' => 'Test Customer',
            'line1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
        ]);

        $order = Order::create([
            'order_number' => 'ORD-1001',
            'user_id' => $customer->id,
            'subtotal' => 34.00,
            'total_amount' => 34.00,
            'shipping_address_id' => $shipping->id,
            'billing_address_id' => $shipping->id,
        ]);

        $order->items()->create([
            'product_variant_id' => $product->variants()->first()->id,
            'quantity' => 1,
            'unit_price' => 34.00,
            'subtotal' => 34.00,
        ]);
    }
}
