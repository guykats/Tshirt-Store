<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Phase 1: Photographic Product Imagery Layer')->value('id');

        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Render real photo URLs as actual images instead of silently falling back to Star-of-David art',
                'description' => "The schema and admin CRUD this epic asks for already exist and don't need to be rebuilt: designs.mockup_url and product_images.url (see App\\Models\\ProductImage, migration 2026_07_21_111000_create_product_images_table.php) are already plain free-text columns, and ProductManagement.jsx already has a full add/edit/delete UI for product images (grep 'imageForm'/'handleImageSubmit'). The real gap is downstream: every one of those url values is fed straight into resources/js/components/DesignArt.jsx as `motif`, which does `REGISTRY[motif] || REGISTRY['star-of-david']` (line ~169) — a fixed lookup table of 9 SVG components. If an admin actually types a real photograph URL (e.g. 'https://cdn.example.com/tee-front.jpg') into that already-working image form, REGISTRY has no matching key, so DesignArt silently renders the Star-of-David fallback instead of the photo — there is currently no code path anywhere (DesignArt.jsx, GarmentMockup.jsx, ProductGallery.jsx) that renders an `<img>` tag at all. Fix: teach GarmentMockup.jsx (used by both Catalog.jsx cards and ProductDetail.jsx via ProductGallery.jsx) to distinguish a real image reference (e.g. starts with 'http://', 'https://', or '/') from a known DesignArt motif keyword, and render a real `<img src=... alt=...>` — sized/cropped to match the existing aspect-square treatment GarmentMockup already uses — instead of composing DesignArt SVG art, while every existing motif-keyword value (star-of-david, menorah, chai, shalom, hamsa, pomegranate, aleph, olive-branch, hebrew-script) keeps rendering exactly as it does today. Do this once in the shared GarmentMockup component so Catalog cards, ProductDetail's main image, and the ProductGallery thumbnail strip all pick it up automatically. Feature/unit tests: a product_images row (or design.mockup_url) with a real URL renders an <img> with that src and the row's alt_text (or a sensible fallback); a motif-keyword value still renders the existing SVG DesignArt output unchanged; an unrecognized non-URL string still falls back to the existing star-of-david SVG default rather than a broken <img>. This task must land and be verified before the companion Creative Agent task on this epic seeds any real photo URLs, since without this fix those URLs would silently render as Star-of-David icons.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => $epicId,
                'title' => 'Source and seed real photographic imagery for the existing catalog',
                'description' => "Depends on the companion Dev Agent task on this epic (GarmentMockup must render a real image URL as an `<img>` rather than falling back to Star-of-David SVG art) — do not seed real photo URLs until that lands and its tests pass. Once it has: this app deliberately has no AI image-generation pipeline and no file-upload/object-storage infrastructure (see DatabaseSeeder.php's own comment: 'we don't have raster image generation available, so product artwork is rendered as clean SVG line art'; see also product_images.url's migration comment noting it's 'a plain text field an admin fills in — either a real external image URL or one of the existing DesignArt motif keywords'), so the only viable source for initial photography is real, license-appropriate, externally-hosted stock apparel photography URLs (e.g. a stock/CC0 apparel photo library) — pick a real source and record which one and its license terms in the task's completion notes. For each of the 7 existing products (Product::all(), see DatabaseSeeder.php's \$catalog array for names/colors), add one or more product_images rows (or set designs.mockup_url) via a data migration pointing at a real photo URL that plausibly matches that product's garment type and color options, with real alt_text. Keep the existing DesignArt/GarmentMockup SVG art as the brand-identity layer alongside the new photography, not replaced by it — this epic is additive (per its own description: 'alongside (not replacing) the existing DesignArt/GarmentMockup rendering, which stays as the actual brand-identity art'), e.g. photography as the primary Catalog card / ProductDetail hero image with the SVG motif still available as one of the gallery thumbnails, or whatever concrete layout reads best once you can see it rendered. Verify visually with a Playwright screenshot of both Catalog.jsx and ProductDetail.jsx showing real photographic images, not just SVG icons, per CLAUDE.md's screenshot-evidence bar.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Render real photo URLs as actual images instead of silently falling back to Star-of-David art',
            'Source and seed real photographic imagery for the existing catalog',
        ])->delete();
    }
};
