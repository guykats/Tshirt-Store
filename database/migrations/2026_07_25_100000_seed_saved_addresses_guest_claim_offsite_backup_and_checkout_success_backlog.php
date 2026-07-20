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
                'title' => 'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order',
                'description' => 'The Address model already has a user_id relation and an is_default column (app/Models/Address.php:8-16), but nothing in the codebase ever reads or writes is_default, and CheckoutController::store() unconditionally does $buyer->addresses()->create([...]) on every single order (app/Http/Controllers/Api/CheckoutController.php:139-142) — even for a logged-in repeat customer who already has an address on file. AccountSettings.jsx only renders a change-password form; there is no address list, edit, delete, or "set as default" UI anywhere. Net effect: every checkout forces retyping the full shipping address from scratch, and the addresses table accumulates unbounded duplicate rows per returning customer. Add address CRUD (list/create/update/delete/set-default) under an authenticated /api/account/addresses resource, reusing Address\'s existing validation shape from CheckoutController\'s current inline address handling. Add an "Addresses" section to AccountSettings.jsx (list with edit/delete/set-default, a form reusing whatever address-field component Checkout.jsx already has for consistency). Update CheckoutController::store() so a logged-in customer can pick a saved address (defaulting to is_default) instead of always creating a new one, while guests keep creating a fresh address exactly as today. Bilingual EN/HE copy. Feature tests: CRUD ownership scoping (a customer cannot edit/delete another customer\'s address), set-default flips exactly one is_default per user, and checkout using a saved address does not create a duplicate Address row.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Checking out as a guest permanently blocks that email from ever registering a real account',
                'description' => 'CheckoutController::store() creates a real users row for guests with is_guest => true (app/Http/Controllers/Api/CheckoutController.php:77-82), and AuthController::register()\'s validation is \'email\' => [\'required\', \'email\', \'unique:users,email\'] with no is_guest carve-out (app/Http/Controllers/Api/AuthController.php:21). Once someone checks out as a guest, that email is permanently consumed by the unique constraint — if they later try to register a real password-protected account with the same email (which is the natural next step after "guest order lookup" was shipped), they get a generic "email has already been taken" validation error forever, with no self-service way to claim/upgrade the guest row into a real account. Fix AuthController::register() so that when the unique(email) match is an existing is_guest => true user with no password set, registration instead sets a password on that existing row (claims it) rather than rejecting — carry over its order history for free since it\'s the same user_id, and flip is_guest to false. Log a SystemEvent for the claim. Feature tests: registering with a guest-only email claims that row and its prior orders remain attached; registering with an email already tied to a real (non-guest) account is still rejected as taken; a freshly claimed account can log in with the new password.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Nightly database backups are written only to local disk on the same host they are meant to protect',
                'description' => 'BackupDatabase::handle() dumps via mysqldump straight to config(\'backup.path\') on the same box (app/Console/Commands/BackupDatabase.php:29-49), and rotate() only prunes local files (lines 108-129) — there is no upload step to any off-site/object storage anywhere in the command. Backup-failure alerting now exists (the BackupFailed email notification), but that only covers the mysqldump-fails case; a full server or disk loss destroys production data and every single backup simultaneously, since they all live in the same place. Add an off-site copy step to BackupDatabase::handle() after a successful local dump: upload the dump to a configured Laravel filesystem disk (e.g. a new "backups" disk in config/filesystems.php, S3-compatible, configured via env vars that default to disabled/no-op in dev so this never blocks on missing credentials per this repo\'s standing rule about deferred secrets) and log a SystemEvent (\'backup.offsite_uploaded\' / \'backup.offsite_failed\') mirroring the existing local-backup logging pattern. If the off-site disk isn\'t configured, log once at info level and skip cleanly rather than failing the whole command — the local backup must still succeed either way. Feature tests using Storage::fake() for the configured disk: a successful local backup with off-site configured uploads and logs success; an upload failure (Storage::fake with a forced exception, or asserting via a fake disk that intentionally errors) logs failure without deleting the local dump; and with the off-site disk unconfigured, the command completes exactly as it does today (no crash, existing tests continue to pass).',
                'agent_name' => 'Ops Agent',
                'task_type' => 'chore',
            ],
            [
                'title' => 'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step',
                'description' => 'Checkout.jsx\'s paid-status render (resources/js/pages/Checkout.jsx:83-90) is two lines of plain text — an h1 with t(\'checkout_success\') and a paragraph with the order number — with no DesignArt brand motif (the reusable component already used elsewhere, e.g. on About), no call-to-action link back into the catalog or to order tracking, and no order summary. This is the single highest-emotion moment in the purchase flow, immediately before the already-branded order-confirmation email arrives, and today it visually undersells that moment compared to the polish already applied to emails and empty states elsewhere in the app. Redesign the paid-status block: add an appropriate DesignArt motif (with a real aria-label per this repo\'s accessibility convention, not aria-hidden, since it\'s here to convey a real congratulatory moment rather than being purely decorative filler — use judgment on which is more appropriate given DesignArt\'s existing label/decorative prop pattern), a clear CTA (e.g. "Continue Shopping" back to the catalog, and if order tracking exists a link to it), and a brief order summary (items/total) if that data is already available on the order object returned to this view without an extra API call. Bilingual EN/HE copy for any new strings in resources/js/i18n/index.js. Take a Playwright screenshot of the redesigned success screen in both EN and HE as evidence.',
                'agent_name' => 'Creative Agent',
                'task_type' => 'feature',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'Checkout always creates a brand-new address row — no saved-address management, full re-entry every order',
            'Checking out as a guest permanently blocks that email from ever registering a real account',
            'Nightly database backups are written only to local disk on the same host they are meant to protect',
            'The post-purchase order-confirmation screen is a plain-text dead end with no motif or next step',
        ])->delete();
    }
};
