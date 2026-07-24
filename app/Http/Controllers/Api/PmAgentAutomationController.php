<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemEvent;
use App\Services\GitHubActionsClient;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use RuntimeException;

class PmAgentAutomationController extends Controller
{
    protected const NOT_CONFIGURED_MESSAGE = 'GitHub Actions automation control isn\'t configured yet — add GITHUB_ACTIONS_TOKEN to enable this.';

    protected const UPSTREAM_ERROR_MESSAGE = 'GitHub rejected the request — check the token\'s permissions.';

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
