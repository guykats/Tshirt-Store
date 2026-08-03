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
                'title' => 'Task-screenshot lightbox on /dashboard/progress has no keyboard escape route and an empty alt on the enlarged image',
                'description' => "ProjectProgress.jsx:342-349 opens a full-screen screenshot lightbox (fixed inset-0 backdrop) with only onClick on the backdrop div to close it -- there's no onKeyDown for Escape, no role=\"dialog\"/aria-modal=\"true\", and no focus is moved into or trapped inside it, so a keyboard-only user who opens a screenshot via the row's image thumbnail has no way to close it and get focus back. The enlarged <img> itself also has alt=\"\" (line 347), which is correct for a decorative image but wrong here -- this is the actual evidence screenshot for a project_tasks row, i.e. meaningful content, and a screen-reader user gets nothing describing what's being shown. Fix by adding an Escape key handler and role=\"dialog\"/aria-modal on the backdrop, moving focus to a close control on open and restoring it to the triggering thumbnail on close, and giving the <img> a real alt derived from the task title (e.g. `\${task.title} screenshot`).",
                'agent_name' => 'Creative Agent',
                'task_type' => 'bug',
            ],
            [
                'title' => 'Floating admin dashboard link is pinned with physical right-5/bottom-5 instead of the app\'s own RTL-safe logical positioning',
                'description' => "Layout.jsx:70-74 positions the site-wide floating admin link (added for Team Management access) with className=\"fixed right-5 bottom-5 ...\". Every other positioned element in the app already uses Tailwind's logical-property utilities (text-start/text-end, ms-2/ps-5, and the inset-inline-end fix already shipped for WishlistButton -- see Faq.jsx:62, Checkout.jsx:56, SizeGuide.jsx:45-58) specifically so they auto-mirror when document.documentElement.dir flips to rtl for Hebrew (Layout.jsx:16-17 sets that on every toggleLocale call). right-5/bottom-5 are physical properties that never flip, so in Hebrew mode this admin link still hugs the visual bottom-right corner instead of moving to the reading-direction-correct corner like the rest of the site. Fix by replacing right-5 with the logical end-5 (Tailwind's inset-inline-end utility) on the Link's className.",
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
            'Task-screenshot lightbox on /dashboard/progress has no keyboard escape route and an empty alt on the enlarged image',
            'Floating admin dashboard link is pinned with physical right-5/bottom-5 instead of the app\'s own RTL-safe logical positioning',
        ])->delete();
    }
};
