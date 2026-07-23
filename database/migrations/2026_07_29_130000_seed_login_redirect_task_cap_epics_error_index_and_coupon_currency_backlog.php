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
                'title' => "Login.jsx sends every successfully-logged-in non-admin customer straight back to the login page",
                'description' => "Login.jsx's handleSubmit (resources/js/pages/Login.jsx:19-29) unconditionally calls navigate('/dashboard') after a successful login, regardless of the logged-in user's role. But every /dashboard* route is wrapped in RequireAdmin (resources/js/App.jsx:46-53, applied at :127-223), which renders <Navigate to=\"/login\" replace /> for any user.role !== 'admin'. Register.jsx's sibling flow (resources/js/pages/Register.jsx:28) correctly navigates to '/' instead, confirming this is a bug, not intentional design. Failure scenario: any regular customer enters correct credentials, the request succeeds and their session is valid, but they're immediately bounced back to a blank-looking login form with no error message — the single most-hit authenticated flow in the app appears completely broken for non-admin users. Fix: navigate('/') (or wherever a logged-in customer's landing page should be) for non-admin users, mirroring Register.jsx's behavior, and only route admins to /dashboard.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "ProjectTaskController::index hard-caps the task list at 200 rows with no pagination, silently hiding older tasks as the board grows",
                'description' => "ProjectTaskController::index (app/Http/Controllers/Api/ProjectTaskController.php:16-24) applies ->limit(200)->get() with no page parameter, while the 'counts' tally returned alongside it reflects the unfiltered global total with no cap — so the summary numbers and the actual rows silently disagree once the board exceeds 200. ProjectProgress.jsx also derives its agent-filter dropdown options from whatever subset of tasks happened to load, so agents whose tasks fall outside the 200-row window can disappear from the filter list too. Distinct from the already-tracked 'status-count tiles ignore filters' bug — this is a hard truncation of the row data itself with no indication to the viewer that anything was cut off. Fix: add real pagination (or a much higher, intentional limit plus a 'showing X of Y' indicator) to index().",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Epics.jsx has no error handling on its load or decide fetches",
                'description' => "Epics.jsx's loadEpics (resources/js/pages/Epics.jsx:19-21) and decideEpic (resources/js/pages/Epics.jsx:24-27) both call api.get/api.post with no .catch() — a failed load leaves epics as [], rendering the identical epics_empty ('nothing proposed') message a genuinely empty queue would show, and a failed approve/reject/delay click silently does nothing with no feedback to the admin. Same class of silent-failure bug already flagged elsewhere (Dashboard widgets, Orders.jsx, Wishlist.jsx, account-addresses) but not yet tracked for the Epics page specifically, where it's especially costly: an admin could believe there's simply nothing to review when the API is actually erroring, stalling the entire epic-approval pipeline invisibly. Fix: add .catch() to both, surfacing a visible error state/toast consistent with how other pages in this batch of fixes are expected to handle it.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "system_events.actor_type has no database index despite being a first-class AuditLog filter",
                'description' => "The system_events table migration (database/migrations/2026_07_16_130855_create_system_events_table.php:15-21) indexes event_type and created_at but not actor_type, even though SystemEventController::index (app/Http/Controllers/Api/SystemEventController.php:18) filters on it directly (->when(\$request->query('actor_type'), ...)) and AuditLog.jsx exposes a user-facing actor-type filter dropdown in the admin UI. Every 'user' vs 'system' actor-type filter on the audit log forces a full table scan; as system_events accumulates rows from every order/design/product/coupon/backup/review action logged across the app, this filtered query degrades linearly with nothing to fall back on. Fix: add a migration indexing actor_type (or a composite (actor_type, created_at) index to also help the common filtered-and-sorted-by-recency case).",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "CouponManagement.jsx hardcodes a literal \"$\" for fixed-value coupons instead of the app's own locale-aware currency formatter",
                'description' => "CouponManagement.jsx's coupon table (resources/js/pages/CouponManagement.jsx:238: coupon.type === 'percent' ? \${coupon.value}% : \$\${coupon.value}) hand-concatenates a bare dollar sign instead of calling resources/js/lib/formatPrice.js, the helper built specifically — per its own doc comment — to replace exactly this 'hand-concatenated {currency} {amount}' pattern app-wide, and already used correctly for base_price in ProductManagement.jsx. Net effect: a Hebrew-locale admin sees an unlocalized, English-ordered '\$' symbol for every fixed-amount coupon in an otherwise bilingual admin tool, and the symbol is silently wrong for any coupon denominated in a non-USD currency since the currency code is never read at all. Fix: call formatPrice(coupon.value, coupon.currency ?? 'USD', locale) in place of the hardcoded template string, matching ProductManagement.jsx's pattern.",
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
        DB::table('project_tasks')->where('title', "Login.jsx sends every successfully-logged-in non-admin customer straight back to the login page")->delete();
        DB::table('project_tasks')->where('title', "ProjectTaskController::index hard-caps the task list at 200 rows with no pagination, silently hiding older tasks as the board grows")->delete();
        DB::table('project_tasks')->where('title', "Epics.jsx has no error handling on its load or decide fetches")->delete();
        DB::table('project_tasks')->where('title', "system_events.actor_type has no database index despite being a first-class AuditLog filter")->delete();
        DB::table('project_tasks')->where('title', "CouponManagement.jsx hardcodes a literal \"$\" for fixed-value coupons instead of the app's own locale-aware currency formatter")->delete();
    }
};
