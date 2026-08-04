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
                'title' => 'Deleting a saved address never refreshes the promoted new default in AccountSettings.jsx, leaving no address marked default until reload',
                'description' => "app/Http/Controllers/Api/AddressController.php:99-123 — destroy() correctly promotes the next-most-recent remaining address to `is_default = true` when the deleted address was the default (line 118-119), but returns a bare `response()->json(null, 204)` with no body. resources/js/pages/AccountSettings.jsx's handleDeleteAddress (~line 121) only does `setAddresses((list) => list.filter((a) => a.id !== id))` — it drops the deleted row from local state but never learns which remaining address the backend just promoted. Failure scenario: a customer with 2+ saved addresses deletes their current default; the backend correctly flips a different row's `is_default`, but the UI shows zero addresses marked 'Default' until a full page reload, making it look like the account lost its default address entirely. Fix by either having destroy() return the promoted address (if any) and merging it into local state, or refetching the address list after a successful delete.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Returning guest checkout customers get a false "please log in" 409 for a password they were never given',
                'description' => "app/Http/Controllers/Api/CheckoutController.php:112-115 checks `User::where('email', \$data['email'])->exists()` to decide whether a checkout email belongs to an existing account, without excluding `is_guest` rows. Guest checkout (same file, ~line 120-122) creates a normal `User` with `is_guest = true` and a random, never-shared password specifically so the customer never needs to register. Failure scenario: a guest who checked out once returns for a second guest checkout with the same email — they hit the 409 'An account already exists for this email. Please log in to continue checkout.' but have no password to log in with, and no path forward except contacting support. Fix by excluding `is_guest` accounts from the duplicate check (letting a repeat guest checkout proceed, or pointing them at Register/Forgot Password instead of a dead-end login prompt).",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Wishlist page always shows generic design motif art instead of the product\'s real photo, unlike Catalog and ProductDetail',
                'description' => "resources/js/pages/Wishlist.jsx:49 renders `<DesignArt motif={item.product.design?.mockup_url} .../>` unconditionally, while resources/js/pages/Catalog.jsx:315-316 prefers the real uploaded photo first (`product.images?.[0]?.url || product.design?.mockup_url`). This isn't just a frontend oversight: app/Http/Controllers/Api/WishlistController.php eager-loads `product.design` and `product.variants` on every action (index line 21, store lines 44/63) but never `product.images`, so the real photo data isn't even in the response payload for the frontend to use. Failure scenario: for any product that has real photography (per the already-shipped Photographic Product Imagery epic), the Wishlist page still shows the generic Star-of-David-style motif art instead of the actual product photo the customer saved, undermining the imagery work's whole point on this one page. Fix by eager-loading `product.images` in WishlistController and applying the same images-first fallback DesignArt already gets in Catalog/ProductDetail.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Catalog.jsx trust-strip uses physical sm:text-left instead of the app\'s own logical text-start convention, so it stays left-aligned for Hebrew (RTL) visitors',
                'description' => "resources/js/pages/Catalog.jsx:191 sets the trust-strip item wrapper to `text-center sm:text-left` — a physical alignment class — while the rest of this bilingual app consistently uses Tailwind's logical `text-start`/`text-end` for exactly this reason (see resources/js/pages/Checkout.jsx:56, Faq.jsx:62, SizeGuide.jsx:45/49, ProductManagement.jsx:481). Failure scenario: a Hebrew-locale visitor on desktop viewports (≥640px) sees the trust-strip icons/text pinned to the physical left edge of each card instead of mirroring to the right/start edge like every other multi-column text block in the app, breaking the RTL reading flow this section is otherwise part of. Fix by swapping `sm:text-left` for `sm:text-start`.",
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
            'Deleting a saved address never refreshes the promoted new default in AccountSettings.jsx, leaving no address marked default until reload',
            'Returning guest checkout customers get a false "please log in" 409 for a password they were never given',
            'Wishlist page always shows generic design motif art instead of the product\'s real photo, unlike Catalog and ProductDetail',
            'Catalog.jsx trust-strip uses physical sm:text-left instead of the app\'s own logical text-start convention, so it stays left-aligned for Hebrew (RTL) visitors',
        ])->delete();
    }
};
