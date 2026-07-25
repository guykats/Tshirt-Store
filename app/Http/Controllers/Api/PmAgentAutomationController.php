<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $configuredToken = config('services.pm_agent.board_token');

        if (! $configuredToken) {
            return response()->json(['message' => 'PM_AGENT_BOARD_TOKEN is not configured.'], 503);
        }

        $providedToken = (string) $request->header('X-PM-Agent-Token', '');

        if (! hash_equals($configuredToken, $providedToken)) {
            abort(403);
        }

        $hasApprovedTodo = ProjectTask::query()->where('status', 'todo')->where('approved_for_dev', true)->exists();

        if ($hasApprovedTodo) {
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
}
