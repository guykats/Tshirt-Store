<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $epics = [
            [
                'title' => 'Custom Design Studio',
                'description' => 'Let customers pick a motif, color, and short text and preview it on a shirt before ordering, instead of choosing only from fixed SKUs. Turns the catalog into a configurable product platform — a strong differentiator and a natural higher-margin upsell.',
            ],
            [
                'title' => 'International & Multi-Currency Expansion',
                'description' => 'Non-USD currency display and checkout, localized shipping estimates, and a broader locale set beyond EN/HE. Widens the addressable market beyond a single-currency, two-language storefront.',
            ],
            [
                'title' => 'Wholesale / B2B Channel',
                'description' => 'A bulk-order flow for synagogues, Judaica retailers, and bar/bat mitzvah event planners: tiered pricing, purchase orders, and net-terms invoicing alongside the existing retail checkout. A second revenue channel and a diversification story.',
            ],
            [
                'title' => 'Loyalty, Referral & Repeat-Purchase Program',
                'description' => 'Points on purchase, referral codes with a reward for both sides, and a simple repeat-customer discount. Directly targets retention and LTV — the metrics investors ask about right after acquisition cost.',
            ],
            [
                'title' => 'Content & SEO Growth Engine',
                'description' => 'A Journal section — short pieces on the meaning behind each motif (menorah, chai, hamsa, pomegranate) — extending the brand-story work already shipped on the About page into an organic-acquisition channel that reduces paid-ads dependency.',
            ],
            [
                'title' => 'Investor Traction Dashboard',
                'description' => 'A dedicated, presentation-ready metrics view (revenue over time, order volume, average order value, repeat-purchase rate) separate from the internal ops dashboard — built specifically to be opened live in a pitch meeting.',
            ],
        ];

        foreach ($epics as $i => $epic) {
            DB::table('epics')->insert(array_merge($epic, [
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now->copy()->addSeconds($i),
                'updated_at' => $now->copy()->addSeconds($i),
            ]));
        }
    }

    public function down(): void
    {
        DB::table('epics')->where('agent_name', 'Visioner Agent')->where('status', 'proposed')->delete();
    }
};
