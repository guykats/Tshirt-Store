<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $epicId = DB::table('epics')->where('title', 'Custom Design Studio')->value('id');

        DB::table('project_tasks')->insert([
            [
                'epic_id' => $epicId,
                'title' => 'Add backend support for a customer-chosen custom design (motif + color + short text) attached to a checkout order',
                'description' => "The 'Custom Design Studio' epic asks for shoppers to pick a motif, color, and short text and preview it on a shirt before ordering, instead of choosing only from a product's fixed, admin-defined variant matrix (App\\Http\\Controllers\\Api\\Admin\\ProductVariantController). Today CheckoutController::store() (app/Http/Controllers/Api/CheckoutController.php) only ever accepts a required product_variant_id (line ~57) resolved straight to an existing ProductVariant row (line ~129) — there is no path for an order line to carry a customer-authored design at all. The building blocks already exist server-adjacent: resources/js/components/DesignArt.jsx's REGISTRY (line 156) is the authoritative, finite set of valid motif keys ('star-of-david', 'menorah', 'chai', 'shalom', 'hamsa', 'pomegranate', 'aleph', 'olive-branch', 'hebrew-script'), and resources/js/components/ColorSwatch.jsx's SWATCH_HEX dictionary is the authoritative, normalized set of valid color names — both are currently frontend-only constants with no server-side counterpart to validate against. Fix: (1) add a custom_designs table (product_id, motif, color, custom_text nullable max ~40 chars, surcharge_price) plus a nullable custom_design_id FK on order_items, with a CustomDesign model using the #[Fillable([...])] attribute pattern (see app/Models/ProductVariant.php); (2) mirror the DesignArt motif keys and ColorSwatch color keys as PHP-side validation lists (e.g. a config/custom_design.php allowed-motifs array, kept in sync with the JS REGISTRY/SWATCH_HEX — leave a comment on both sides pointing at each other) so an unknown motif or color 422s instead of silently persisting garbage; (3) extend CheckoutController::store() to accept an optional custom_design payload (product_id, motif, color, custom_text) as an alternative to product_variant_id for products whose Design allows customization, computing unit_price as the product's base_price plus a configurable surcharge (e.g. config('custom_design.surcharge', 8.00)) the same way unit_price is already computed from price_override today. Component/feature test: submitting checkout with a valid custom_design payload persists a custom_designs row linked to the resulting order_item and prices it at base_price + surcharge; an unknown motif or a custom_text over the length limit is rejected with a 422 and no rows are created.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'epic_id' => $epicId,
                'title' => "Build the customer-facing Design Studio picker with a live GarmentMockup preview",
                'description' => "Once the backend accepts a custom_design payload (see the companion backend task on this epic), shoppers need an actual UI to build one. resources/js/components/GarmentMockup.jsx already renders a real garment silhouette (tee/hoodie) in a given color with a DesignArt motif composited on the chest as the print (line 177: <DesignArt motif={motif} tone={...} background={false} />), and already accepts motif/color/product as props — it was built anticipating exactly this kind of live preview, but nothing today drives it from user input; every current call site passes a fixed motif/color from the product's own data. Fix: build a 'Design Studio' picker (either a dedicated route, e.g. /design/:productSlug, or an inline panel on ProductDetail.jsx for products flagged as customizable) with: a motif <select>/radio grid built from the same finite motif list DesignArt.jsx's REGISTRY exposes (export the keys, or a small labeled array, from DesignArt.jsx rather than hardcoding a second copy in the picker); a color picker reusing ColorSwatch.jsx's existing normalized dictionary (the same shared dictionary the companion GarmentMockup color-fallback bug fix on this board consolidates color resolution around); and a short free-text input (character-counted, capped client-side to match the backend's limit) for a name/word/date to print. Every change to any of the three inputs should immediately re-render an on-page <GarmentMockup motif={motif} color={color} product={product} label={...} /> so the shopper sees their actual selection before committing — text itself doesn't need to render inside the SVG art for this task (DesignArt has no text-compositing slot today); showing the motif+color combination live is the core deliverable, with the custom text shown as a plain label under/beside the preview rather than baked into the art. Wire the 'add to cart'/checkout action to send the custom_design payload the backend task defines. Add real English + Hebrew i18n keys for every new label (per CLAUDE.md's bilingual-by-default rule) and follow the app's existing accessibility conventions (aria-pressed on the motif/color toggle buttons, paired label/id on the text input). Component test: changing the motif or color selection updates the GarmentMockup props/rendered output; submitting sends the expected custom_design payload shape.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'epic_id' => $epicId,
                'title' => 'Surface a custom design order line on the admin fulfillment view and the PDF invoice',
                'description' => "Once an order can carry a custom_designs row (see the companion backend task on this epic), whoever actually prints and ships the garment needs to see what was chosen — right now every admin order surface (app/Http/Resources/OrderItemResource.php, the admin order/fulfillment views, resources/views/invoices/order.blade.php) only ever renders productVariant->size/color/product->name, with no concept of a customer-authored motif/color/text at all. Fix: extend OrderItemResource to include the linked custom_design's motif, color, and custom_text (nullable — most order items still reference a plain product_variant and should render exactly as today), surface it clearly in whichever admin view staff use to pick/pack orders (matching the existing size/color display pattern) so fulfillment staff can tell 'print CHAI in navy, text: MAZEL TOV' apart from a plain fixed-SKU line item, and add the same fields to the invoice PDF template (resources/views/invoices/order.blade.php) so the customer's own receipt also shows exactly what they designed. Follow the bilingual convention already used for payment_status on this same invoice template (motif names should resolve through a lang key the same way, not render as a raw internal slug like 'olive-branch'). Feature test: an order containing a custom_design item renders its motif/color/text in the OrderItemResource JSON and the generated invoice PDF text; a plain (non-custom) order item is unaffected.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Add backend support for a customer-chosen custom design (motif + color + short text) attached to a checkout order',
            'Build the customer-facing Design Studio picker with a live GarmentMockup preview',
            'Surface a custom design order line on the admin fulfillment view and the PDF invoice',
        ])->delete();
    }
};
