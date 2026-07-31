<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Phase 2: Catalog Depth - From 7 to a Believable Full Assortment')->value('id');

        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways',
                'description' => "Only 7 products exist today (see DatabaseSeeder.php's \$catalog array), all built from resources/js/components/DesignArt.jsx's fixed 9-entry REGISTRY. Three of those 9 motifs (chai, shalom, aleph) are trivially cheap to vary — they're each just `<HebrewMark text=\"...\" fontSize={...} />`, not custom SVG artwork — so the lowest-risk way to add real variety without new illustration work is: add several new REGISTRY entries in DesignArt.jsx reusing HebrewMark with new, meaningful Hebrew words that fit the brand's restrained voice (see the existing product descriptions in DatabaseSeeder.php for tone — e.g. words like אמונה/faith, ברכה/blessing, תקווה/hope, אהבה/love — pick real, correctly-spelled words and confirm meaning before using them, this is user-facing religious/cultural content and must be accurate), plus optionally new colorways of existing products (new ProductVariant color options beyond the current Black/Sand). Stay strictly within the existing model: no new product 'type' beyond the existing tee/hoodie framing already used by products 1-7, no new category, no new infrastructure — same constraint the epic itself sets ('t-shirts only, nothing more complex' per the owner's explicit instruction).\n\nCRITICAL gotcha: DatabaseSeeder.php only runs on `migrate:fresh --seed` (local dev/tests) — production only ever runs `php artisan migrate --force` (see deploy.yml), never re-seeds. Adding new products only to DatabaseSeeder.php would make them appear in every local/test environment but NEVER reach the live production catalog. The new products must be created by a real data migration (`DB::table('designs')->insert(...)` / `DB::table('products')->insert(...)` / `DB::table('product_variants')->insert(...)`, mirroring the exact fields DatabaseSeeder.php's loop sets: designs.status='approved' with approved_by/approved_at, products.status='active', unique slug/sku per product, variant SKUs following the existing `{product_sku}-{size}-{3-letter-color}` pattern) — that migration is what actually ships to production. Also add the same new catalog entries to DatabaseSeeder.php's \$catalog array so fresh local/test databases stay consistent with production (the verification bar's `migrate:fresh --seed --force` run should produce the same catalog).\n\nWrite full bilingual name/description copy for each new product per CLAUDE.md's bilingual-by-default rule — check whether product name/description are looked up through resources/js/i18n/index.js or stored directly per-row (follow whatever DatabaseSeeder.php's existing products already do). Feature tests: catalog listing/pagination returns the new product count; each new product's variants have unique SKUs and pass the existing product/variant validation; DesignArt renders each new motif without falling back to the default. Aim for roughly double the current 7 products (not an exact count) — stop once the catalog reads as a believable full assortment rather than forcing an arbitrary number.",
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
        DB::table('project_tasks')->where('title', 'Grow the catalog from 7 to ~14 products using new HebrewMark-style motifs and colorways')->delete();
    }
};
