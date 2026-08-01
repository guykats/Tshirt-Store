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
                'title' => 'Storefront filled buttons never get the app\'s own branded focus ring, unlike the admin/Team-Management pages',
                'description' => "resources/js/pages/Discover.jsx:13 defines a shared FOCUS_RING constant (focus-visible:ring-2 focus-visible:ring-brass ...) and it's used consistently on filled buttons in Discover.jsx, Epics.jsx, VisionerChat.jsx, ProjectProgress.jsx, and TeamManagementNav.jsx (grep -rl FOCUS_RING resources/js returns only those 5 files). But the primary customer-facing storefront -- Login.jsx, Register.jsx, Checkout.jsx (e.g. the bg-ink 'Continue to Payment' button at Checkout.jsx:79), Catalog.jsx, ProductDetail.jsx, Dashboard.jsx, Wishlist.jsx, Orders.jsx, AccountSettings.jsx -- has the same filled-button pattern but zero focus-visible usage anywhere. Keyboard users tabbing through checkout, login, and the catalog get only the bare browser outline instead of the branded ring the rest of the system already ships. Fix by extracting FOCUS_RING into a shared constant (e.g. resources/js/lib/focusRing.js) and applying it to the primary action buttons across the storefront pages listed above.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Wishlist heart button uses hardcoded right-3 instead of the app\'s own RTL-safe logical positioning, so it doesn\'t mirror in Hebrew',
                'description' => "Catalog.jsx:325, Wishlist.jsx:55, and ProductDetail.jsx:165 all position the wishlist heart with <WishlistButton className=\"absolute top-3 right-3\" />. The app is otherwise deliberately built RTL-first with Tailwind logical-property utilities (text-start/text-end, ms-2/ps-5 -- see Faq.jsx:62, Checkout.jsx:56, SizeGuide.jsx:45-58, AccountSettings.jsx:249) specifically so they auto-mirror when document.documentElement.dir flips to rtl for Hebrew. right-3 is a physical property that never flips, so in Hebrew mode every product image's heart icon still hugs the visual right edge instead of moving with reading direction like every other positioned element on the site. Fix by replacing right-3 with the logical end-3 (Tailwind's inset-inline-end utility) on all three WishlistButton usages.",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'External/new-tab links (tracking, invoice, GitHub commit) give no visual or screen-reader warning before the context change',
                'description' => "OrderCard.jsx:66-74 (carrier tracking link), Checkout.jsx:84-89 (tracking link on the success screen), Orders.jsx:64-71 (invoice download), and ProjectProgress.jsx:320-329 (GitHub commit link) all use target=\"_blank\" with plain link text and no icon or sr-only cue -- a screen-reader user gets no warning the link opens a new tab (WCAG 3.2.5), and sighted users get no visual cue either. This is the one interaction pattern implemented identically four times across the app that never received the small polish treatment the rest of the interactive elements got. Fix by adding a small external-link icon plus an sr-only \"(opens in a new tab)\" suffix to the shared link styling used for these four links.",
                'agent_name' => 'Creative Agent',
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
            'Storefront filled buttons never get the app\'s own branded focus ring, unlike the admin/Team-Management pages',
            'Wishlist heart button uses hardcoded right-3 instead of the app\'s own RTL-safe logical positioning, so it doesn\'t mirror in Hebrew',
            'External/new-tab links (tracking, invoice, GitHub commit) give no visual or screen-reader warning before the context change',
        ])->delete();
    }
};
