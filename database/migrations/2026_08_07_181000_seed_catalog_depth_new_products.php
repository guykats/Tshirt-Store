<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Production-path counterpart to the same 7 new $catalog entries added to
     * DatabaseSeeder.php for local/test environments — see CLAUDE.md's "Production
     * state changes through git" and the gotcha this task description spells out:
     * DatabaseSeeder only runs on `migrate:fresh --seed` (local/test), production only
     * ever runs `php artisan migrate --force`, so new demo/launch products only reach
     * the live catalog through a real data migration like this one.
     *
     * Same low-risk approach as the existing chai/shalom/aleph products: no new
     * illustration, just new REGISTRY entries in DesignArt.jsx reusing HebrewMark with
     * real, correctly-spelled, meaningful Hebrew words (each verified before use — see
     * DesignArt.jsx's REGISTRY comment). Mirrors exactly the fields
     * DatabaseSeeder.php's loop sets: designs.status='approved' with approved_by/
     * approved_at, products.status='active', unique slug/sku per product, variant SKUs
     * following the existing `{product_sku}-{size}-{3-letter-color}` pattern.
     */
    private const PRODUCTS = [
        [
            'motif' => 'emunah',
            'title' => 'Emunah Mark',
            'name' => 'Emunah Mark Tee',
            'slug' => 'emunah-mark-tee',
            'description' => 'אמונה — "faith." Five letters, one steady line, for a conviction that doesn\'t need to explain itself.',
            'price' => 32.00,
            'type' => 'tee',
            'colors' => ['Black', 'Sand', 'Charcoal'],
        ],
        [
            'motif' => 'bracha',
            'title' => 'Bracha Mark',
            'name' => 'Bracha Mark Tee',
            'slug' => 'bracha-mark-tee',
            'description' => 'ברכה — "blessing." A word reached for constantly — over bread, over wine, over each other — printed here as quietly as it\'s meant.',
            'price' => 32.00,
            'type' => 'tee',
            'colors' => ['Black', 'Sand'],
        ],
        [
            'motif' => 'tikvah',
            'title' => 'Tikvah Script',
            'name' => 'Tikvah Script Hoodie',
            'slug' => 'tikvah-script-hoodie',
            'description' => 'תקווה — "hope." The word that gave Israel\'s national anthem its name, set large and calm across heavyweight fleece.',
            'price' => 68.00,
            'type' => 'hoodie',
            'colors' => ['Black'],
        ],
        [
            'motif' => 'ahava',
            'title' => 'Ahava Mark',
            'name' => 'Ahava Mark Tee',
            'slug' => 'ahava-mark-tee',
            'description' => 'אהבה — "love." Four letters that carry the weight without needing any decoration.',
            'price' => 34.00,
            'type' => 'tee',
            'colors' => ['Black', 'Sand', 'Charcoal'],
        ],
        [
            'motif' => 'simcha',
            'title' => 'Simcha Mark',
            'name' => 'Simcha Mark Tee',
            'slug' => 'simcha-mark-tee',
            'description' => 'שמחה — "joy." The word Hebrew reaches for at every celebration, kept as understated as the rest of the collection.',
            'price' => 34.00,
            'type' => 'tee',
            'colors' => ['Black', 'Sand'],
        ],
        [
            'motif' => 'emet',
            'title' => 'Emet Mark',
            'name' => 'Emet Mark Tee',
            'slug' => 'emet-mark-tee',
            'description' => 'אמת — "truth." Three letters at the center of an old story about what gives something life — here, simply a mark worth wearing.',
            'price' => 32.00,
            'type' => 'tee',
            'colors' => ['Black', 'Sand'],
        ],
        [
            'motif' => 'or',
            'title' => 'Or Mark',
            'name' => 'Or Mark Hoodie',
            'slug' => 'or-mark-hoodie',
            'description' => 'אור — "light." The oldest word for the oldest idea, set in a serif built to last.',
            'price' => 68.00,
            'type' => 'hoodie',
            'colors' => ['Black'],
        ],
    ];

    /** Same Wikimedia-hosted photography DatabaseSeeder.php's $photoLibrary reuses per silhouette. */
    private const TEE_PHOTO = [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/81/Camiseta-negra.jpg',
        'alt_text' => 'Plain black crew-neck cotton T-shirt, folded flat against a white background.',
    ];

    private const HOODIE_PHOTO = [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/83/Kapuzensweater.jpg',
        'alt_text' => 'Plain black pullover hoodie hanging against a white background.',
    ];

    public function up(): void
    {
        $now = now();

        $adminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');

        // Existing products use SKUs TS-001..TS-007 (see DatabaseSeeder.php) — continue
        // the same sequence rather than reusing/guessing a number, so a re-run against a
        // catalog that's grown further some other way still can't collide.
        $nextSkuNumber = 8;

        foreach (self::PRODUCTS as $item) {
            // Idempotent: re-running this migration (e.g. a redeploy replay) shouldn't
            // duplicate a product that already exists.
            if (DB::table('products')->where('slug', $item['slug'])->exists()) {
                $nextSkuNumber++;

                continue;
            }

            $designId = DB::table('designs')->insertGetId([
                'title' => $item['title'],
                'category' => 'cultural-signal',
                'mockup_url' => $item['motif'],
                'source_agent' => 'creative_agent',
                'status' => 'approved',
                'approved_by' => $adminId,
                'approved_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sku = 'TS-'.str_pad((string) $nextSkuNumber, 3, '0', STR_PAD_LEFT);
            $nextSkuNumber++;

            $productId = DB::table('products')->insertGetId([
                'design_id' => $designId,
                'name' => $item['name'],
                'slug' => $item['slug'],
                'description' => $item['description'],
                'base_price' => $item['price'],
                'currency' => 'USD',
                'sku' => $sku,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $variantRows = [];
            foreach ($item['colors'] as $color) {
                foreach (['S', 'M', 'L', 'XL'] as $size) {
                    $variantRows[] = [
                        'product_id' => $productId,
                        'size' => $size,
                        'color' => $color,
                        'sku' => "{$sku}-{$size}-".strtoupper(substr($color, 0, 3)),
                        'stock_quantity' => 25,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('product_variants')->insert($variantRows);

            $photo = $item['type'] === 'hoodie' ? self::HOODIE_PHOTO : self::TEE_PHOTO;

            DB::table('product_images')->insert([
                [
                    'product_id' => $productId,
                    'url' => $photo['url'],
                    'alt_text' => "{$photo['alt_text']} ({$item['name']})",
                    'color' => null,
                    'position' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'product_id' => $productId,
                    'url' => $item['motif'],
                    'alt_text' => "{$item['title']} — the brand's single-line SVG art mark for this design.",
                    'color' => null,
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // Namespaced catalog cache (App\Services\CatalogCache) would otherwise serve a
        // stale, pre-migration product list for up to its 5-minute TTL after this lands.
        \App\Services\CatalogCache::flush();
    }

    public function down(): void
    {
        foreach (self::PRODUCTS as $item) {
            $productId = DB::table('products')->where('slug', $item['slug'])->value('id');

            if (! $productId) {
                continue;
            }

            $designId = DB::table('products')->where('id', $productId)->value('design_id');

            DB::table('product_images')->where('product_id', $productId)->delete();
            DB::table('product_variants')->where('product_id', $productId)->delete();
            DB::table('products')->where('id', $productId)->delete();
            DB::table('designs')->where('id', $designId)->delete();
        }

        \App\Services\CatalogCache::flush();
    }
};
