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
                'title' => "VisionerChat.jsx's message-history load has no error handling",
                'description' => "resources/js/pages/VisionerChat.jsx:17-21 - load() does api.get('/api/visioner-chat').then((res) => setMessages(res.data.data)) with no .catch(), and useEffect(load, []) fires it directly on mount. If the initial fetch fails (network blip, 401 after session expiry, 500), messages stays [] and the page renders the same empty-chat copy as a genuinely history-less conversation, with no error indicator or retry. Every other list-load in the admin dashboard (Epics.jsx, AuditLog.jsx, CouponManagement.jsx) already sets an error state on failure - this is the one chat page still missing it. Fix: add a .catch() that sets an error state and renders it distinctly from the empty-chat message.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "AgentStatusResource runs 3 extra queries per row instead of one aggregate query",
                'description' => "app/Http/Resources/AgentStatusResource.php:17-26 runs two ProjectTask::where('agent_name', ..)->where('status', ..)->latest(..)->first() lookups plus a third ->where('status', 'todo')->count(), all scoped per agent, inside toArray(). AgentStatusController::index (app/Http/Controllers/Api/AgentStatusController.php:17) calls AgentStatusResource::collection(AgentStatus::orderBy('agent_name')->get()), so this fires 3 separate queries against the (now 170+ row and growing) project_tasks table for every one of the 5 seeded agents - 15 queries for a single GET /api/agent-statuses call that should be a couple of grouped aggregate queries computed once. Fix: precompute the live-task and backlog-count maps once in the controller (e.g. one GROUP BY agent_name, status query plus one GROUP BY agent_name todo count) and inject them into each resource instance instead of re-querying per row.",
                'agent_name' => 'Ops Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "Epics proposed via the Visioner Agent chat never write a SystemEvent",
                'description' => "app/Http/Controllers/Api/VisionerChatController.php's proposeEpic() (lines 72-93) creates a new Epic row (status: 'proposed') whenever the Claude tool call succeeds, but unlike every other epic-status mutation in the same domain - EpicController::approve/reject/delay (app/Http/Controllers/Api/EpicController.php:39,60,82), each of which calls SystemEvent::log('epic.approved'/'epic.rejected'/'epic.delayed', ..) - it never calls SystemEvent::log(). A repo-wide grep for 'epic.proposed' or 'epic.created' finds no such event type anywhere. So the audit log has a visible entry for every decision made about an epic but a silent gap for the moment it was actually created - an admin auditing 'where did this epic come from' via AuditLog.jsx alone can't see it originate, only the chat transcript shows it. Fix: add SystemEvent::log('epic.proposed', ..., 'Visioner Agent', 'system', ['epic_id' => \$epic->id]) in proposeEpic(), mirroring the three sibling decision actions, and add the new event type to AuditLog.jsx's event-type filter.",
                'agent_name' => 'Dev Agent',
                'task_type' => 'bug',
                'status' => 'todo',
                'approved_for_dev' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'epic_id' => null,
                'title' => "CouponManagement.jsx renders coupon expiry dates in a fixed English format regardless of site language",
                'description' => "resources/js/pages/CouponManagement.jsx:241-242 renders new Date(coupon.expires_at).toLocaleDateString() with no locale argument, so it always formats in the browser's default (effectively US English) date style even when the admin has the site set to Hebrew - while every other locale-aware surface in the app (formatPrice, used throughout) respects i18n.language. An admin viewing the coupon table in Hebrew sees every coupon's expiry rendered in an English-locale date format sitting directly beside Hebrew UI text. Fix: pass i18n.language into a locale-aware date formatter (mirroring formatPrice's pattern) instead of calling the parameterless toLocaleDateString().",
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
        DB::table('project_tasks')->where('title', "VisionerChat.jsx's message-history load has no error handling")->delete();
        DB::table('project_tasks')->where('title', "AgentStatusResource runs 3 extra queries per row instead of one aggregate query")->delete();
        DB::table('project_tasks')->where('title', "Epics proposed via the Visioner Agent chat never write a SystemEvent")->delete();
        DB::table('project_tasks')->where('title', "CouponManagement.jsx renders coupon expiry dates in a fixed English format regardless of site language")->delete();
    }
};
