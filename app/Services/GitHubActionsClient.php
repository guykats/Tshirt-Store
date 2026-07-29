<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubActionsClient
{
    protected const API_VERSION = '2022-11-28';

    protected function client(): PendingRequest
    {
        $token = config('services.github_actions.token');

        if (! $token) {
            throw new RuntimeException('GITHUB_ACTIONS_TOKEN is not configured.');
        }

        return Http::withToken($token)->withHeaders([
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => self::API_VERSION,
        ]);
    }

    protected function workflowUrl(string $suffix = ''): string
    {
        $owner = config('services.github_actions.owner');
        $repo = config('services.github_actions.repo');
        $workflow = config('services.github_actions.workflow_file');

        return "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/{$workflow}{$suffix}";
    }

    /**
     * @return array{state: string, enabled: bool}
     */
    public function state(): array
    {
        $response = $this->client()->get($this->workflowUrl())->throw();

        $state = $response->json('state');

        return ['state' => $state, 'enabled' => $state === 'active'];
    }

    public function enable(): void
    {
        $this->client()->put($this->workflowUrl('/enable'))->throw();
    }

    public function disable(): void
    {
        $this->client()->put($this->workflowUrl('/disable'))->throw();
    }

    /**
     * Re-enables the workflow if (and only if) it's currently disabled -
     * called right when a task/epic gets approved, since a disabled
     * workflow can never fire on its own schedule to notice new approved
     * work exists. Without this, auto-disable-when-idle has no counterpart:
     * once off, it stays off until a human manually flips the dashboard
     * toggle, no matter how much gets approved in the meantime. Returns
     * true only if it actually flipped disabled -> enabled, so the caller
     * can decide whether to log anything; false for "already enabled",
     * "not configured", or any upstream error - none of those should ever
     * block the approval action itself.
     */
    public function enableIfDisabled(): bool
    {
        try {
            $state = $this->state();
        } catch (RuntimeException|RequestException $e) {
            return false;
        }

        if ($state['enabled']) {
            return false;
        }

        try {
            $this->enable();
        } catch (RequestException $e) {
            return false;
        }

        return true;
    }
}
