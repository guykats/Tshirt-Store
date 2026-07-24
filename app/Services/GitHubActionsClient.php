<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
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
}
