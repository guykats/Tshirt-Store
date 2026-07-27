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
                'title' => "BackupDatabase's rotation only prunes local dumps, never the off-site copies it also uploads",
                'description' => "app/Console/Commands/BackupDatabase.php:177-200 (rotate()) globs and deletes only files under the local \$backupDir; the off-site upload path (uploadOffsite(), lines 92-145) writes every successful backup to config('backup.offsite_disk') but nothing ever deletes an old off-site object. config/backup.php's 'keep' retention window (default 14) is honored locally but silently ignored off-site, so once a maintainer configures offsite_disk (currently deferred, same posture as PayPal/SMTP per CLAUDE.md), off-site storage grows unbounded forever instead of matching the documented retention policy. Fix by having rotate() also list and delete objects beyond the retention window on the configured offsite_disk (skipping cleanly when offsite_disk is null, as today). Feature test: with Storage::fake() standing in for the offsite disk, seed more than \$keep off-site objects and assert rotate() prunes them down to the retention window, logging a SystemEvent the same way local pruning does.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "WishlistButton ignores the wishlist's loading state, so an already-saved product's heart renders unfilled until the fetch resolves",
                'description' => "resources/js/components/WishlistButton.jsx:15,18 destructures only isWishlisted/toggle from useWishlist(), never loading. WishlistContext.jsx initializes productIds as an empty Set and only populates it once GET /api/wishlist resolves (WishlistContext.jsx:14-31), exposing loading specifically so consumers can distinguish 'not yet known' from 'genuinely not wishlisted'. Because WishlistButton never reads it, isWishlisted(product.id) is evaluated against the still-empty Set on every fresh page load, so a customer revisiting a product they already wishlisted sees an unfilled (unwishlisted-looking) heart for a beat — or indefinitely on a slow connection — before it snaps to filled once the fetch completes, which reads as a UI bug/flicker rather than a loading state. Fix by having WishlistButton also read loading and suppress rendering the filled/unfilled distinction (e.g. a neutral/disabled state) until it's false. Component test: render WishlistButton while WishlistContext's fetch is still pending and assert it doesn't render the unwishlisted (empty-heart) state for a product known to be wishlisted once the fetch resolves.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => "Checkout's saved-address fetch has no error handling, silently reverting a logged-in customer to blank manual entry instead of their address book",
                'description' => "resources/js/pages/Checkout.jsx:148-156 does api.get('/api/account/addresses').then(...) with no .catch(). If that request fails (network error, session hiccup, 500), savedAddresses stays [] and usingSavedAddress (Checkout.jsx:164, 'user && savedAddresses.length > 0 && selectedAddressOption !== \\'new\\'') silently falls back to full manual entry — a logged-in customer with saved addresses on file has to retype one from scratch with no error message telling them why their address book didn't load, indistinguishable from a customer who never saved one. This is a different failure mode from the already-tracked #106/#109/#122/#129 pattern (those cover a fetch masking as 'genuinely empty' data): here the page stays fully usable, but degrades a returning customer's experience with no signal anything went wrong. Fix by adding a .catch() that surfaces a dismissible inline notice (e.g. 'Couldn't load your saved addresses — enter one below') distinct from a hard error, matching the tone of other soft-degradation messaging in the app. Component test: mock the addresses fetch to reject and assert the checkout form still renders and is submittable, with the notice shown.",
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
            "BackupDatabase's rotation only prunes local dumps, never the off-site copies it also uploads",
            "WishlistButton ignores the wishlist's loading state, so an already-saved product's heart renders unfilled until the fetch resolves",
            "Checkout's saved-address fetch has no error handling, silently reverting a logged-in customer to blank manual entry instead of their address book",
        ])->delete();
    }
};
