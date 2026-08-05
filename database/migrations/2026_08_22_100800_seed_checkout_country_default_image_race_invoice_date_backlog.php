<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $backlog = [
            [
                'title' => 'Checkout and account-address country selector defaults to "United States" regardless of the shopper\'s site language, even though a real locale-aware country dropdown now exists',
                'description' => "resources/js/pages/Checkout.jsx:121 and resources/js/pages/AccountSettings.jsx:10/77 all initialize address state with country: 'US' (or address.country || 'US'), never derived from i18n.language. Both forms now render a real <select> populated via shippingCountryOptions(i18n.language) (resources/js/lib/countryNames.js), whose own comment explains US and IL are listed first 'since this bilingual EN/Hebrew store's two primary markets' — so the earlier bug where no country field rendered at all is already fixed, but the default value inside that dropdown is still hardcoded English-market-first. A Hebrew-preferring shopper browsing the fully-RTL, fully-translated storefront still lands on checkout and account address forms with 'United States' pre-selected and must manually change it on every single order. Fix: default country to i18n.language === 'he' ? 'IL' : 'US' when initializing address/form state in both files.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'ProductImageController::store assigns gallery position via an unlocked max()+1 read, unlike its sibling reorder() which at least operates in bulk',
                'description' => "app/Http/Controllers/Api/Admin/ProductImageController.php:20-23's store() computes \$maxPosition = \$product->images()->max('position') and inserts the new image at \$maxPosition + 1 with no row lock or transaction around the read-then-insert. Two concurrent image uploads to the same product — two admins working the same product, or a slow double-submit from two tabs — can both read the same max() value and both insert with the identical position, leaving the gallery with a duplicate position value and a permanent gap where the next position should have been. This is a distinct root cause from the already-tracked 'ProductImageController::reorder has no transaction' item (that one is a missing transaction around a bulk update; this one is a missing lock on an aggregate read before a single insert). Fix: wrap the position computation and insert in a DB::transaction() with a lockForUpdate() on the product's images, mirroring the row-lock pattern already used elsewhere in this codebase (e.g. CheckoutController::store's variant lock).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "The PDF invoice's date field is never locale-formatted, unlike every other value on the same document",
                'description' => "resources/views/invoices/order.blade.php:140 renders {{ \$order->created_at->format('Y-m-d') }} — a raw, unlocalized ISO date. Every other value on the same template has been through a real localization pass: currency values use Number::currency(..., locale: app()->getLocale()) (lines 204-232), text alignment flips via \$startAlign/\$endAlign based on \$isRtl, and payment_status is translated through lang/{en,he}/invoice.php. A Hebrew-locale customer downloading their invoice sees a fully-translated, RTL-aligned document with real Hebrew currency formatting, but a bare ISO date string that matches nothing else on the page. Fix: replace the raw format('Y-m-d') call with a locale-aware formatter (e.g. Carbon's translatedFormat(), or PHP's IntlDateFormatter keyed to app()->getLocale()), consistent with how Number::currency() is already used two lines away.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
        ];

        foreach ($backlog as $task) {
            DB::table('project_tasks')->insert(array_merge([
                'epic_id' => null,
                'commit_sha' => null,
                'screenshot_path' => null,
                'blocked_reason' => null,
                'completed_at' => null,
            ], $task, [
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Checkout and account-address country selector defaults to "United States" regardless of the shopper\'s site language, even though a real locale-aware country dropdown now exists',
            'ProductImageController::store assigns gallery position via an unlocked max()+1 read, unlike its sibling reorder() which at least operates in bulk',
            "The PDF invoice's date field is never locale-formatted, unlike every other value on the same document",
        ])->delete();
    }
};
