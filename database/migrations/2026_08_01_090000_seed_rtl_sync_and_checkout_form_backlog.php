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
                'title' => 'A returning Hebrew-preferring visitor gets Hebrew text inside a still-LTR document on every fresh page load',
                'description' => "resources/js/i18n/index.js:1429 initializes i18next with lng: localStorage.getItem('locale') || 'en', so a returning visitor who previously toggled to Hebrew gets Hebrew strings from the very first render. But document.documentElement.dir/lang are only ever set inside Layout.jsx's toggleLocale() (lines 12-18) -- the click handler for the manual toggle button. Nothing runs that same sync on initial load: resources/views/app.blade.php:2 renders <html lang=\"{{ app()->getLocale() }}\"> with no dir attribute at all, driven by the server-side Laravel locale (always 'en' by default, since the language preference is purely a client-side localStorage value never sent to the server), and resources/js/app.jsx's bootstrap (just createRoot(...).render(<App />) after importing ./i18n) never reads i18n.language/localStorage to set dir/lang on mount either. Concrete scenario: a Hebrew-preferring customer bookmarks the site, closes the tab, and reopens it (or just hits refresh) -- every page renders Hebrew copy inside an <html> still defaulting to dir=\"ltr\" and lang=\"en\", so text alignment, punctuation placement, and any dir-sensitive CSS are wrong until they happen to click the locale toggle again (which flips language right back to English first, then needs a second click). Fix: run the same document.documentElement.dir/lang assignment Layout.jsx's toggleLocale does once on initial mount, keyed off i18n.language.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => 'Checkout has no <form> element, so pressing Enter after filling in the address never submits the order',
                'description' => "Checkout.jsx's address/coupon field group (resources/js/pages/Checkout.jsx, the 'entering' status branch) is a bare <div> -- there is no <form> tag anywhere in the file (grepped for '<form', zero matches). The 'Continue to Payment' action (lines 301-307) is a <button onClick={() => createOrder().catch(() => {})}> with no type/form association, unlike a real <form onSubmit={...}> would provide. Concrete scenario: a shopper tabs through the address fields and, out of habit, presses Enter after typing the last field (or the coupon code) instead of clicking the button -- because there is no enclosing <form>, the browser has nothing to submit and the keypress does nothing at all, with zero error feedback; the only way to actually proceed is a precise mouse/touch click on the button, which is both a usability regression from every other data-entry flow in the app (Login.jsx, Register.jsx, AccountSettings.jsx addresses, etc. all use real <form onSubmit>) and a WCAG operability expectation for pointer-independent operation. Fix: wrap the address/coupon fields in a <form onSubmit={(e) => { e.preventDefault(); createOrder().catch(() => {}); }}> and give the button type=\"submit\", matching the pattern already used elsewhere in the app.",
                'agent_name' => 'Creative Agent',
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
            'A returning Hebrew-preferring visitor gets Hebrew text inside a still-LTR document on every fresh page load',
            'Checkout has no <form> element, so pressing Enter after filling in the address never submits the order',
        ])->delete();
    }
};
