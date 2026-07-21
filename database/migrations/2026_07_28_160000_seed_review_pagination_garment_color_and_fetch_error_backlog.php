<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('project_tasks')->insert([
            [
                'epic_id' => null,
                'title' => 'Product review list silently caps at 100 while the reported review count is unlimited',
                'description' => "app/Http/Controllers/Api/ReviewController.php:20-33 (index()) fetches at most 100 reviews (->limit(100)->get()) but meta.count comes from a separate, unlimited \$product->reviews()->count(). resources/js/components/ProductReviews.jsx renders every row from data with no pagination or \"load more\" control. For any product that accumulates over 100 reviews, the page displays a count that doesn't match the number of review cards actually rendered, and the 101st+ reviews become permanently unreachable by any shopper - there is no way to request them. Fix: add real pagination to ReviewController::index() (cursor or page-based, matching the pattern already used for catalog/admin-queue pagination) and wire ProductReviews.jsx to request further pages. Not the same gap as task #75 (admin queue pagination), which covers a different controller/page entirely.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color',
                'description' => "resources/js/components/GarmentMockup.jsx:13-34 defines PALETTES = { Black: {...}, Sand: {...} } and resolves via PALETTES[color] || PALETTES.Sand - an exact, case-sensitive key match. resources/js/components/ColorSwatch.jsx, by contrast, ships a ~30-entry, case-insensitive, normalized color dictionary (navy, charcoal, burgundy, forest, mustard, etc.) because color is validated as free-text string (App\\Http\\Controllers\\Api\\Admin\\ProductVariantController, 'color' => ['required','string','max:50']). Practical effect: a shopper who selects \"Navy\", \"Charcoal\", or even lowercase \"black\" on ProductDetail.jsx sees the garment mockup silently fall back to the beige Sand color, contradicting the correctly-colored swatch dot rendered right next to the same label by ColorSwatch. Fix: reuse (or mirror) ColorSwatch's normalized SWATCH_HEX dictionary inside GarmentMockup to derive palette tones from the actual hex instead of maintaining a second, much smaller, case-sensitive name list.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Wishlist and account-addresses fetches have no .catch(), so a failed request is indistinguishable from genuinely empty',
                'description' => "resources/js/pages/Wishlist.jsx:20-24 does api.get('/api/wishlist').then((res) => setItems(res.data.data)).finally(() => setLoading(false)) with no .catch(). If the request fails (network error, 500, session hiccup), items stays [] and the page renders the \"Your wishlist is empty\" EmptyState exactly as it would for a genuinely empty wishlist - a logged-in customer with saved items can be told they have none. The same missing-.catch() pattern exists on the address list fetch in resources/js/pages/AccountSettings.jsx:39 (api.get('/api/account/addresses').then((res) => setAddresses(res.data.data))). This is the identical bug class already fixed for ProductDetail.jsx (#106) and tracked for Catalog.jsx (#109), but on two pages neither of those titles covers. Fix: add error state handling (a retry-capable error message, matching the pattern established by #106) to both fetches.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Product review list silently caps at 100 while the reported review count is unlimited',
            'GarmentMockup only recognizes two hard-coded, case-sensitive color names - most variant colors render the wrong garment color',
            'Wishlist and account-addresses fetches have no .catch(), so a failed request is indistinguishable from genuinely empty',
        ])->delete();
    }
};
