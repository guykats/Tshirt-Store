<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('epics')->insert([
            [
                'title' => 'Lifecycle Email Engine: Cart Recovery, Welcome, and Win-Back Flows',
                'description' => "A real retention lever, not a UI tweak: the app already sends four transactional emails (OrderConfirmationMail, OrderShippedMail, OrderDeliveredMail, OrderRefundedMail, all via Mail::to()->locale()->send()) but nothing ever emails a shopper who didn't buy. Grounded in 2026 DTC data: average cart abandonment is ~70%, and abandoned-cart emails recover an average 10.2% of that lost revenue (up to 3-4x more for top performers) at ~$3.65+ revenue per recipient (Sender.net / Swell 2026 cart-abandonment reports) - a large, well-proven return for a channel this store has zero presence in today. Scope: (1) a CartRecoveryMail sent a few hours after a checkout starts but never completes, reusing the existing ExpireAbandonedOrders command's own definition of 'abandoned' (app/Console/Commands/ExpireAbandonedOrders.php) as the trigger point, sent before that job releases the reserved stock, not after; (2) a WelcomeMail on first account creation; (3) a scheduled win-back email to registered customers with no order in N days. All three reuse the existing bilingual, locale-aware Blade mail templates and MAIL_MAILER=log dev/testing setup already established for the four order emails - no new provider, no new credential, same Laravel scheduler already wired in bootstrap/app.php. A simple mail_sends log table (or reusing SystemEvent) records what was sent to avoid duplicate sends, which a PM can scope as its own task.",
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Refer-a-Friend Rewards Program',
                'description' => "An acquisition lever suited to an identity-driven niche where word-of-mouth carries more trust than paid ads. 2026 DTC research (Swell, Eightx, Mageloyalty) finds referral-driven CAC runs 60-80% lower than paid social/search, and that combining referral with a simple points-based loyalty mechanic (purchase points + referral bonus + review reward) is the standard playbook for small DTC brands specifically, since customer retention is roughly 5x cheaper than new acquisition. This store already has the two ingredients such a program needs, unconnected: a full Coupon system (app/Models/Coupon.php - code, value, max_redemptions, max_redemptions_per_user, active, expires_at) and a Review system (ProductReviews.jsx, Review model) with no reward attached to leaving one. Scope: a referrals table linking a referring user to a unique, auto-generated personal coupon code (reusing the existing Coupon model/redemption pipeline rather than inventing a second discount system), a referral link/code surfaced on the customer's account page, and a reward coupon issued to the referrer once the referred friend's first order is paid (not just placed, mirroring the existing paid-order gating already used elsewhere in checkout). Deliberately scoped to code-sharing + existing coupon infrastructure, not a paid third-party referral platform - appropriate for a single-catalog store with no traction data yet to justify that spend.",
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Jewish Holiday Capsule Collections',
                'description' => "A product-and-merchandising initiative grounded in what already works in this exact niche: competitors like Jewish Swag Shop, ModernTribe, and Judaica Webstore all run named Hanukkah and Passover apparel collections as a core part of their catalog, not a side experiment - holiday-themed Judaica apparel is a proven, demand-tested category, not a speculative one. Today this store's catalog and DesignArt.jsx motif registry (star-of-david, menorah, chai, hamsa, pomegranate, aleph, olive-branch, hebrew-script, shalom) is entirely evergreen with no holiday or seasonal grouping, and Product has no concept of a collection/season at all. Scope: a lightweight collections table (or a nullable products.collection_slug column) so a set of existing/new products can be grouped under a named capsule (Hanukkah, Passover, Rosh Hashanah, Purim), a public collection landing page reusing the existing Catalog.jsx filtering pattern, and 2-3 new holiday-specific motifs added to the DesignArt registry (a menorah variant, a matzah/Seder-plate motif, a dreidel motif) as real design work for the Creative Agent. Deliberately does not require new generation infrastructure - it is a merchandising and content capability layered on the catalog/design system that already exists, timed to real dates on the Jewish calendar for natural, recurring marketing moments rather than one evergreen catalog with no seasonal hook.",
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Institutional & Group Ordering (Synagogues, Day Schools, Camps)',
                'description' => "A new B2B channel specific to this domain: Judaica retail has a well-established institutional buyer segment distinct from individual consumers - synagogues, Jewish day schools, JCCs, and summer camps routinely place bulk apparel orders (camp t-shirts, youth-group shirts, b'nai mitzvah class shirts), and existing Judaica retailers (The Judaica Place, Benny's/tjssc.com) run dedicated bulk/institutional pricing programs as a standard part of their business, not a novelty. This store's checkout today has no concept of quantity-tiered pricing, purchase orders, or an organization account - Checkout.jsx is a single-shopper, single-address, pay-by-PayPal-now flow only. Scope: an inquiry-first MVP (not full self-service bulk checkout, which is too large a bet for a single-catalog store with no traction data yet) - a public 'Group & Bulk Orders' page describing minimum quantities and turnaround, a simple inquiry form (organization name, contact, item/quantity/size-breakdown, target date) that creates an internal record an admin can see and follow up on manually, similar in spirit to the existing admin-reviewed Design/Order queues. Deliberately excludes automated bulk pricing, net-30 invoicing, or a self-service portal in this first pass - those become their own future epic only once real institutional inquiries validate the demand.",
                'agent_name' => 'Visioner Agent',
                'status' => 'proposed',
                'priority' => 0,
                'decided_by' => null,
                'decided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('epics')->whereIn('title', [
            'Lifecycle Email Engine: Cart Recovery, Welcome, and Win-Back Flows',
            'Refer-a-Friend Rewards Program',
            'Jewish Holiday Capsule Collections',
            'Institutional & Group Ordering (Synagogues, Day Schools, Camps)',
        ])->delete();
    }
};
