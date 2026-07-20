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
                'title' => 'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one',
                'description' => 'ReviewController::store() enforces a unique(product_id, user_id) constraint (see the Review::where(...)->exists() check and the QueryException catch around the actual insert) so a customer can only ever review a given product once, ever — but there is no update/self-delete endpoint anywhere in routes/api.php\'s reviews section, only POST .../reviews (create-once) and DELETE .../reviews/{review} (App\\Policies\\ReviewPolicy::delete(), whose own doc comment says plainly: "Only an admin can remove a review — moderation of abusive/fake content, not something the author or any other customer can do themselves"). That policy is the right call for moderation, but it leaves a legitimate customer with no path to fix a typo, correct a rating after a replacement/refund, or simply change their mind — their only option today is contacting the store to ask an admin to delete it via ReviewController::manage()\'s admin queue so they can resubmit. Add a self-service path: either a PATCH /api/products/{product}/reviews/{review} (rating/body, same throttle:reviews middleware as store/destroy) authorized via a new ReviewPolicy::update() rule scoped to `$review->user_id === $user->id`, or extend the existing DELETE route\'s authorization to also allow the review\'s own author (via Gate::any or an authorize() call that accepts either isAdmin() or ownership) so they can delete-then-resubmit within the existing create-once flow — whichever keeps ReviewPolicy\'s admin-moderation intent for *other people\'s* reviews clearly separate from a user\'s standing right to manage their own. Wire an edit control into the reviews section of ProductDetail.jsx (reusing the existing star-rating input UI) visible only on the current user\'s own review. Write feature tests: the review\'s author can edit/delete their own review, a different customer cannot, and admin moderation deletion still works unchanged.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'Customers have no self-service way to delete their account or request their data be removed',
                'description' => 'grep -n "Route::" routes/api.php turns up /change-password (AuthController::changePassword) and the various profile/order/wishlist reads, but no DELETE route for a user\'s own account anywhere, and AccountSettings.jsx (resources/js/pages/AccountSettings.jsx) has no delete-account control at all — the only way an account or its personal data is ever removed today is a direct database edit, which per this repo\'s "production state changes through git" rule isn\'t even a normal operating procedure. For a store handling EU/Israeli customers this is a real gap, not just a nice-to-have: someone who wants to stop being a customer, or who is exercising a GDPR-style erasure request, has no in-app way to do either. Add a POST /api/account/delete (requires the current password to re-confirm, mirroring how changePassword already re-validates current_password) that anonymizes or soft-deletes the user\'s account — decide up front whether historical Order rows must be retained for accounting/tax purposes (likely yes) and if so scrub personally-identifying fields (name/email replaced with a placeholder, addresses removed) rather than a hard delete that would orphan those orders\' foreign keys; log a SystemEvent so the change is auditable. Add a "Delete Account" section to AccountSettings.jsx (password confirmation input, an explicit type-to-confirm or two-step "are you sure" pattern like Orders.jsx\'s cancel-order confirm flow, and a role guard so an admin account can\'t accidentally self-delete the only admin). Bilingual EN/HE copy throughout. Write feature tests: correct password deletes/anonymizes the account and logs the user out, wrong password is rejected, and past orders remain queryable (e.g. by an admin) after the customer\'s account is gone.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'feature',
            ],
            [
                'title' => 'A failed nightly database backup is only visible in the admin audit log — nobody is actively notified',
                'description' => 'app/Console/Commands/BackupDatabase.php\'s failLoudly() (called when mysqldump fails, exits non-zero, or the backup directory can\'t be created) only ever does `SystemEvent::log(\'backup.failed\', ...)` plus a console error — it never reaches an actual person unless an admin happens to open /dashboard and scroll to the System Events feed (or /dashboard/audit-log) that same day. Contrast this with the existing low-stock pattern: CheckoutController::store() already does `Notification::send(User::where(\'role\', \'admin\')->get(), new LowStockAlert($variantToAlert))` (app/Http/Controllers/Api/CheckoutController.php:172) the moment a real-time condition needs a human\'s attention, using App\\Notifications\\LowStockAlert (Laravel\'s Notification/Mailable combo, so it lands in an inbox, not just a UI feed) — backup failures deserve the same treatment, arguably more so, since a silently-broken backup job could mean zero recoverable backups for weeks with nobody the wiser. Add an App\\Notifications\\BackupFailed notification (mirroring LowStockAlert\'s structure) and send it to `User::where(\'role\', \'admin\')->get()` from BackupDatabase::failLoudly() alongside the existing SystemEvent::log call (keep both — the audit-log entry and the email are complementary, not a replacement for each other). Bilingual subject/body per this repo\'s mail-branding convention (see the branded order emails work). Write a feature test using Process::fake() to simulate a mysqldump failure (the existing BackupDatabaseCommandTest already does this for the SystemEvent assertion) and Notification::fake() to assert every admin user receives BackupFailed exactly once, plus one asserting a successful backup sends no such notification.',
                'agent_name' => 'Ops Agent',
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
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        DB::table('project_tasks')->whereIn('title', [
            'A customer who leaves a review has no way to edit or delete it themselves — only admin moderation can remove one',
            'Customers have no self-service way to delete their account or request their data be removed',
            'A failed nightly database backup is only visible in the admin audit log — nobody is actively notified',
        ])->delete();
    }
};
