<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Epic;
use App\Models\ProjectTask;
use App\Models\SystemEvent;
use App\Services\GitHubActionsClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use RuntimeException;

class PmAgentAutomationController extends Controller
{
    protected const NOT_CONFIGURED_MESSAGE = 'GitHub Actions automation control isn\'t configured yet — add GITHUB_ACTIONS_TOKEN to enable this.';

    protected const UPSTREAM_ERROR_MESSAGE = 'GitHub rejected the request — check the token\'s permissions.';

    /**
     * Read-only, token-gated export of exactly which todo tasks are really
     * approved_for_dev right now. pm-agent.yml fetches this before Claude
     * starts and reconciles its freshly-replayed local database to match —
     * see app/Console/Commands/SyncApprovedTaskTitles.php — since a disposable
     * CI-local SQLite has no other way to see a live "Approve for development"
     * click on the real dashboard.
     */
    public function approvedTodoTitles(Request $request)
    {
        $unauthorized = $this->rejectUnlessTokenValid($request);

        if ($unauthorized) {
            return $unauthorized;
        }

        return response()->json(['configured' => true, 'titles' => $this->approvedTodoTaskTitles()]);
    }

    /**
     * Read-only, token-gated export of the real epics table — title,
     * description, agent_name, status, priority for every row. Epics have the
     * same live-click-invisible-to-CI problem as project_tasks approvals
     * (approve/reject/delay on the live Epics tab writes straight to
     * production), plus a second one project_tasks doesn't have: epics can be
     * created live too, with no migration at all, via VisionerChatController's
     * propose_epic tool — so a CI replay may be missing rows entirely, not
     * just have their status wrong. SyncEpicDecisions handles both.
     */
    public function epicDecisions(Request $request)
    {
        $unauthorized = $this->rejectUnlessTokenValid($request);

        if ($unauthorized) {
            return $unauthorized;
        }

        $epics = Epic::query()->get(['title', 'description', 'agent_name', 'status', 'priority']);

        return response()->json(['configured' => true, 'epics' => $epics]);
    }

    /**
     * Token-authenticated (not Sanctum — called by the pm-agent.yml CI job,
     * which has no browser session), not admin-session-authenticated. Checks
     * REAL production approval state (unlike the disposable-SQLite replay the
     * cron otherwise relies on to view the board) and disables the workflow,
     * server-side, if nothing is approved to build. Runs as a normal
     * production request so the SystemEvent it logs actually lands in the
     * real audit log, unlike anything logged from inside the CI job itself.
     */
    public function disableIfIdle(Request $request, GitHubActionsClient $github)
    {
        $unauthorized = $this->rejectUnlessTokenValid($request);

        if ($unauthorized) {
            return $unauthorized;
        }

        if ($this->approvedTodoTaskTitles()->isNotEmpty()) {
            return response()->json(['disabled' => false, 'reason' => 'approved_work_exists']);
        }

        try {
            $state = $github->state();
        } catch (RuntimeException $e) {
            return response()->json(['message' => self::NOT_CONFIGURED_MESSAGE], 503);
        } catch (RequestException $e) {
            return response()->json(['message' => self::UPSTREAM_ERROR_MESSAGE], 502);
        }

        if (! $state['enabled']) {
            return response()->json(['disabled' => false, 'reason' => 'already_disabled']);
        }

        try {
            $github->disable();
        } catch (RequestException $e) {
            return response()->json(['message' => self::UPSTREAM_ERROR_MESSAGE], 502);
        }

        SystemEvent::log(
            'pm_agent.auto_disabled',
            'The PM Agent workflow disabled itself: no approved_for_dev todo task remained.',
            'pm-agent.yml',
            'system',
        );

        return response()->json(['disabled' => true, 'reason' => 'idle']);
    }

    public function show(Request $request, GitHubActionsClient $github)
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (! config('services.github_actions.token')) {
            return response()->json(['configured' => false, 'enabled' => null, 'state' => null]);
        }

        try {
            $state = $github->state();
        } catch (RequestException $e) {
            return response()->json(['configured' => true, 'enabled' => null, 'state' => null, 'message' => self::UPSTREAM_ERROR_MESSAGE], 200);
        }

        return response()->json(['configured' => true, 'enabled' => $state['enabled'], 'state' => $state['state']]);
    }

    public function enable(Request $request, GitHubActionsClient $github)
    {
        return $this->toggle($request, $github, true);
    }

    public function disable(Request $request, GitHubActionsClient $github)
    {
        return $this->toggle($request, $github, false);
    }

    protected function toggle(Request $request, GitHubActionsClient $github, bool $enable)
    {
        abort_unless($request->user()->isAdmin(), 403);

        try {
            $enable ? $github->enable() : $github->disable();
        } catch (RuntimeException $e) {
            return response()->json(['message' => self::NOT_CONFIGURED_MESSAGE], 503);
        } catch (RequestException $e) {
            return response()->json(['message' => self::UPSTREAM_ERROR_MESSAGE], 502);
        }

        SystemEvent::log(
            $enable ? 'pm_agent.enabled' : 'pm_agent.disabled',
            'The PM Agent autonomous workflow was '.($enable ? 'enabled' : 'disabled')." by {$request->user()->name}.",
            $request->user()->name,
            'user',
        );

        return response()->json([
            'configured' => true,
            'enabled' => $enable,
            'state' => $enable ? 'active' : 'disabled_manually',
        ]);
    }

    /**
     * Shared auth guard for the two CI-facing endpoints above — a JSON error
     * response if the shared secret is missing/wrong, null if the request may
     * proceed. Deliberately not Sanctum: the CI job has no browser session.
     */
    protected function rejectUnlessTokenValid(Request $request)
    {
        $configuredToken = config('services.pm_agent.board_token');

        if (! $configuredToken) {
            return response()->json(['message' => 'PM_AGENT_BOARD_TOKEN is not configured.'], 503);
        }

        $providedToken = (string) $request->header('X-PM-Agent-Token', '');

        if (! hash_equals($configuredToken, $providedToken)) {
            abort(403);
        }

        return null;
    }

    protected function approvedTodoTaskTitles()
    {
        return ProjectTask::query()->where('status', 'todo')->where('approved_for_dev', true)->pluck('title');
    }
}
