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
                'title' => 'ResetPassword.jsx collapses every reset failure into a misleading "link expired" message, even a simple mismatched-confirmation typo',
                'description' => "resources/js/pages/ResetPassword.jsx:34-36 catches any failed POST /api/reset-password and always shows t('reset_password_error') (\"This reset link is invalid or has expired. Please request a new one.\"), never inspecting the response body. PasswordResetController::reset() (app/Http/Controllers/Api/PasswordResetController.php:33-37) validates password with Laravel's confirmed rule before ever checking the token, so a shopper who simply mistypes their password confirmation gets a 422 for that reason alone, yet is told their link expired and sent on a pointless round trip through email instead of just retyping the confirmation. Fix by reading err.response?.data?.errors and rendering the actual field-level message (or at least distinguishing a 422 on password/password_confirmation from a 422 on email/token). Feature/component test: submit ResetPassword with a valid, unexpired token but mismatched password/password_confirmation and assert the shown error is a password-mismatch message, not the expired-link one.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'ProjectProgress.jsx\'s screenshot lightbox is a mouse-only modal with no keyboard escape or focus handling',
                'description' => 'resources/js/pages/ProjectProgress.jsx renders a screenshot thumbnail as a focusable <button> that opens a full-screen lightbox overlay on click. That overlay has no role="dialog"/aria-modal, moves focus nowhere, contains no focusable element (the <img> has empty alt="" and no tabIndex/onKeyDown), and only closes via a mouse onClick on the backdrop — there is no Escape keydown handler anywhere in the component. A keyboard-only admin who opens a screenshot via Enter/Space on the thumbnail button has no way to close the overlay short of reloading the page, which is a hard accessibility failure given CLAUDE.md\'s explicit "accessibility is not optional" stance already followed elsewhere in the app (e.g. DesignArt.jsx\'s label prop pattern). Fix by adding an Escape keydown listener that calls setLightbox(null), giving the overlay role="dialog" + aria-modal="true", and moving focus onto a close control (or the image itself) when it opens. Test: open the lightbox via keyboard activation of the thumbnail button, press Escape, and assert the overlay unmounts.',
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Dashboard.jsx and AuditLog.jsx status/actor-type badges use hardcoded ml-* instead of the logical ms-* margin, so they hug the wrong side in Hebrew RTL',
                'description' => "resources/js/pages/Dashboard.jsx (the agent-status in_progress badge) and resources/js/pages/AuditLog.jsx (the actor-type badge next to an actor's name) both use the physical ml-2/ml-1 (margin-left) utility instead of the logical ms-2/ms-1 (margin-inline-start) that the rest of the app already uses for this exact 'badge next to text' pattern, e.g. AccountSettings.jsx's ms-2 default-address badge. Layout.jsx's language toggle sets document.documentElement.dir = 'rtl' globally when an admin switches to Hebrew, and these Team Management pages render inside the same document, so in RTL both badges end up glued to the wrong edge of their label instead of trailing it naturally. Fix by replacing both ml-2/ml-1 with ms-2/ms-1. Test: render Dashboard's agent-status row and AuditLog's actor cell with i18n.language = 'he'/dir=\"rtl\" and assert the badge's computed margin is on the correct (start) side.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Catalog.jsx\'s homepage trust strip force-aligns text left regardless of the active language, so Hebrew visitors get right-to-left script pinned to the left edge',
                'description' => 'resources/js/pages/Catalog.jsx\'s trust-strip section renders each item (icon, heading, description) with className="text-center sm:text-left" — a hardcoded physical text-left, not the logical text-start that would flip correctly under dir="rtl". Layout.jsx sets document.documentElement.dir = \'rtl\' when a shopper toggles to Hebrew via the header language switch, so at the sm breakpoint and above, Hebrew heading/body text in this section is force-aligned to the physical left edge instead of following the script\'s natural right-to-left flow, inconsistent with the rest of the bilingual storefront. Fix by changing sm:text-left to sm:text-start. Test: render Catalog with i18n.language = \'he\' and assert the trust-strip container\'s computed text-align is right, not left, at the sm breakpoint.',
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
            'ResetPassword.jsx collapses every reset failure into a misleading "link expired" message, even a simple mismatched-confirmation typo',
            'ProjectProgress.jsx\'s screenshot lightbox is a mouse-only modal with no keyboard escape or focus handling',
            'Dashboard.jsx and AuditLog.jsx status/actor-type badges use hardcoded ml-* instead of the logical ms-* margin, so they hug the wrong side in Hebrew RTL',
            'Catalog.jsx\'s homepage trust strip force-aligns text left regardless of the active language, so Hebrew visitors get right-to-left script pinned to the left edge',
        ])->delete();
    }
};
