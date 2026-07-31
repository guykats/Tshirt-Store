<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Real photography, additive alongside (not replacing) the existing SVG
     * DesignArt/GarmentMockup brand art — see GarmentMockup.jsx's `isPhotographicMotif`
     * (already shipped, companion Dev Agent task) and ProductGallery.jsx, both of which
     * already know how to render a real photo URL as an `<img>` and a motif keyword as
     * the existing SVG garment mockup side by side in one gallery.
     *
     * One real photo per garment silhouette (tee / hoodie), reused across every product
     * that uses that silhouette — the same "one generic shape, many designs" approach the
     * SVG mockup already takes — rather than one bespoke photo per product, since no
     * per-product photography exists or is buildable without upload/AI-image
     * infrastructure this app deliberately doesn't have (see DatabaseSeeder.php). Both
     * photos show a real, plain, unbranded garment in Black, which is a genuine color
     * option on every one of these 7 products (tees also offer Sand; that colorway isn't
     * photographed yet — left for a follow-up task).
     *
     * Source + license (both hotlinked directly from Wikimedia Commons' upload.wikimedia.org
     * CDN, verified reachable via `curl -I` before use):
     * - Tee: "Camiseta-negra.jpg" by Commons user Camisetas (camisetas.info), CC BY 3.0.
     *   https://commons.wikimedia.org/wiki/File:Camiseta-negra.jpg — attribution required:
     *   "Camiseta-negra" by Camisetas / camisetas.info, CC BY 3.0
     *   (https://creativecommons.org/licenses/by/3.0).
     * - Hoodie: "Kapuzensweater.jpg", released into the public domain by German Wikipedia
     *   user Mikezaubzer. https://commons.wikimedia.org/wiki/File:Kapuzensweater.jpg — no
     *   attribution required.
     *
     * Each product gets two product_images rows: position 0 is the real photo (the
     * primary image Catalog.jsx cards and ProductDetail's hero now prefer — see
     * Catalog.jsx's `product.images?.[0]?.url || product.design?.mockup_url`), position 1
     * is the product's own DesignArt motif keyword, so the brand's line-art mark stays
     * visible as a ProductGallery thumbnail.
     */
    private const TEE_PHOTO = [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/81/Camiseta-negra.jpg',
        'alt_text' => 'Plain black crew-neck cotton T-shirt, folded flat against a white background.',
    ];

    private const HOODIE_PHOTO = [
        'url' => 'https://upload.wikimedia.org/wikipedia/commons/8/83/Kapuzensweater.jpg',
        'alt_text' => 'Plain black pullover hoodie hanging against a white background.',
    ];

    /** slug => [garment type, design title, motif keyword] */
    private const PRODUCTS = [
        'minimal-star-tee' => ['tee', 'Minimal Star of David', 'star-of-david'],
        'menorah-line-tee' => ['tee', 'Menorah Line Art', 'menorah'],
        'chai-icon-tee' => ['tee', 'Chai Icon Mark', 'chai'],
        'shalom-script-hoodie' => ['hoodie', 'Shalom Script', 'shalom'],
        'hamsa-guard-tee' => ['tee', 'Hamsa Guard Mark', 'hamsa'],
        'rimon-crest-tee' => ['tee', 'Rimon Crest', 'pomegranate'],
        'aleph-tee' => ['tee', 'Aleph Mark', 'aleph'],
    ];

    public function up(): void
    {
        $now = now();

        foreach (self::PRODUCTS as $slug => [$type, $title, $motif]) {
            $productId = DB::table('products')->where('slug', $slug)->value('id');

            // Don't hard-fail a production deploy over a demo product that may not exist
            // in every environment (e.g. it was renamed/removed) — just skip it.
            if (! $productId) {
                continue;
            }

            // Idempotent: re-running this migration (e.g. a redeploy replay) shouldn't
            // duplicate rows.
            $alreadySeeded = DB::table('product_images')
                ->where('product_id', $productId)
                ->whereIn('url', [self::TEE_PHOTO['url'], self::HOODIE_PHOTO['url']])
                ->exists();

            if ($alreadySeeded) {
                continue;
            }

            $photo = $type === 'hoodie' ? self::HOODIE_PHOTO : self::TEE_PHOTO;

            DB::table('product_images')->insert([
                [
                    'product_id' => $productId,
                    'url' => $photo['url'],
                    'alt_text' => "{$photo['alt_text']} ({$title})",
                    'color' => null,
                    'position' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'product_id' => $productId,
                    'url' => $motif,
                    'alt_text' => "{$title} — the brand's single-line SVG art mark for this design.",
                    'color' => null,
                    'position' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        // Namespaced catalog cache (App\Services\CatalogCache) would otherwise serve
        // stale (photo-less) product payloads for up to its 5-minute TTL after this
        // migration lands.
        \App\Services\CatalogCache::flush();
    }

    public function down(): void
    {
        foreach (self::PRODUCTS as $slug => [$type, $title, $motif]) {
            $productId = DB::table('products')->where('slug', $slug)->value('id');

            if (! $productId) {
                continue;
            }

            DB::table('product_images')
                ->where('product_id', $productId)
                ->whereIn('url', [self::TEE_PHOTO['url'], self::HOODIE_PHOTO['url'], $motif])
                ->delete();
        }

        \App\Services\CatalogCache::flush();
    }
};
