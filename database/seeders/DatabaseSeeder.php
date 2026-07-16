<?php

namespace Database\Seeders;

use App\Models\AgentStatus;
use App\Models\Design;
use App\Models\Order;
use App\Models\Product;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo catalog. `motif` maps to a component in resources/js/components/DesignArt.jsx —
     * we don't have raster image generation available, so product "artwork" is rendered as
     * clean SVG line art / typography directly in the browser rather than uploaded images.
     * It's stored in designs.mockup_url (a free-text column) as a pragmatic reuse rather than
     * a real asset URL.
     */
    protected array $catalog = [
        [
            'motif' => 'star-of-david',
            'title' => 'Minimal Star of David',
            'name' => 'Minimal Star Tee',
            'slug' => 'minimal-star-tee',
            'description' => 'A clean, understated take on a timeless symbol. Single-line construction, no ornamentation — the shape speaks for itself.',
            'price' => 34.00,
            'type' => 'tee',
        ],
        [
            'motif' => 'menorah',
            'title' => 'Menorah Line Art',
            'name' => 'Menorah Line Tee',
            'slug' => 'menorah-line-tee',
            'description' => 'Seven branches, one continuous line. A quiet nod to light and continuity, drawn with the same restraint as the rest of the collection.',
            'price' => 36.00,
            'type' => 'tee',
        ],
        [
            'motif' => 'chai',
            'title' => 'Chai Icon Mark',
            'name' => 'Chai Icon Tee',
            'slug' => 'chai-icon-tee',
            'description' => 'חי — "life." Two letters, centered, in a serif built for permanence rather than trend.',
            'price' => 32.00,
            'type' => 'tee',
        ],
        [
            'motif' => 'shalom',
            'title' => 'Shalom Script',
            'name' => 'Shalom Script Hoodie',
            'slug' => 'shalom-script-hoodie',
            'description' => 'A single word, centered on heavyweight fleece. שלום carries the collection\'s whole philosophy: say less, mean more.',
            'price' => 68.00,
            'type' => 'hoodie',
        ],
        [
            'motif' => 'hamsa',
            'title' => 'Hamsa Guard Mark',
            'name' => 'Hamsa Guard Tee',
            'slug' => 'hamsa-guard-tee',
            'description' => 'An open palm, reduced to its essential geometry. Protection as a design language, not a costume.',
            'price' => 34.00,
            'type' => 'tee',
        ],
        [
            'motif' => 'pomegranate',
            'title' => 'Rimon Crest',
            'name' => 'Rimon Crest Tee',
            'slug' => 'rimon-crest-tee',
            'description' => 'The pomegranate, traditionally counted at 613 seeds — rendered here as a single quiet crest on the chest.',
            'price' => 34.00,
            'type' => 'tee',
        ],
        [
            'motif' => 'aleph',
            'title' => 'Aleph Mark',
            'name' => 'Aleph Tee',
            'slug' => 'aleph-tee',
            'description' => 'א — the first letter, the beginning of everything. The simplest mark in the collection, and the most deliberate.',
            'price' => 32.00,
            'type' => 'tee',
        ],
    ];

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

        $firstVariant = null;

        foreach ($this->catalog as $i => $item) {
            $design = Design::create([
                'title' => $item['title'],
                'category' => 'cultural-signal',
                'mockup_url' => $item['motif'],
                'status' => 'approved',
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ]);

            $product = Product::create([
                'design_id' => $design->id,
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'base_price' => $item['price'],
                'sku' => 'TS-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'status' => 'active',
            ]);

            $colors = $item['type'] === 'hoodie' ? ['Black'] : ['Black', 'Sand'];
            foreach ($colors as $color) {
                foreach (['S', 'M', 'L', 'XL'] as $size) {
                    $variant = $product->variants()->create([
                        'size' => $size,
                        'color' => $color,
                        'sku' => "{$product->sku}-{$size}-".strtoupper(substr($color, 0, 3)),
                        'stock_quantity' => 25,
                    ]);
                    $firstVariant ??= $variant;
                }
            }
        }

        // Two more designs still pending approval, to demo the human-in-the-loop dashboard.
        Design::create([
            'title' => 'Hebrew Script Streetwear',
            'category' => 'cultural-signal',
            'mockup_url' => 'hebrew-script',
            'status' => 'pending_approval',
        ]);

        Design::create([
            'title' => 'Olive Branch Line Art',
            'category' => 'cultural-signal',
            'mockup_url' => 'olive-branch',
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
            'subtotal' => $firstVariant->product->base_price,
            'total_amount' => $firstVariant->product->base_price,
            'shipping_address_id' => $shipping->id,
            'billing_address_id' => $shipping->id,
        ]);

        $order->items()->create([
            'product_variant_id' => $firstVariant->id,
            'quantity' => 1,
            'unit_price' => $firstVariant->product->base_price,
            'subtotal' => $firstVariant->product->base_price,
        ]);

        foreach ([
            ['agent_name' => 'Orchestrator', 'status' => 'idle', 'current_task' => 'Coordinated Milestones 3-5 + Phase 2 brand redesign; shipped to store.guykats.com'],
            ['agent_name' => 'Trend Agent', 'status' => 'idle', 'current_task' => 'Wrote demo catalog copy for 7 products with cultural context'],
            ['agent_name' => 'Creative Agent', 'status' => 'idle', 'current_task' => 'Designed 9 original SVG motifs (Star of David, Menorah, Chai, Shalom, Hamsa, Rimon, Aleph, Hebrew Script, Olive Branch) + brand design system'],
            ['agent_name' => 'Dev Agent', 'status' => 'idle', 'current_task' => 'Built PayPal checkout, invoice/email automation, dashboard, 33 API tests'],
            ['agent_name' => 'Ops Agent', 'status' => 'idle', 'current_task' => 'Wired PDF invoice generation + localized order confirmation emails'],
        ] as $agent) {
            AgentStatus::create($agent);
        }

        SystemEvent::log('system.seeded', 'Database seeded with demo data.', 'DatabaseSeeder', 'system');
    }
}
