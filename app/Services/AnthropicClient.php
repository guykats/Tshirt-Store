<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AnthropicClient
{
    protected const BASE_URL = 'https://api.anthropic.com/v1/messages';

    protected const API_VERSION = '2023-06-01';

    protected const MAX_TOOL_ROUNDS = 5;

    protected function client()
    {
        $apiKey = config('services.anthropic.api_key');

        if (! $apiKey) {
            throw new RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        return Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ])->acceptJson();
    }

    /**
     * Run a conversation to completion, executing any tool calls the model makes along
     * the way via $toolHandlers, and return the assistant's combined text reply plus a
     * list of every tool call that was actually executed (for the caller to log/link).
     *
     * @param  array<int, array{role: string, content: mixed}>  $messages
     * @param  array<int, array{name: string, description: string, input_schema: array}>  $tools
     * @param  array<string, callable(array): array>  $toolHandlers  name => fn(array $input): array (tool_result content)
     * @return array{text: string, tool_calls: array<int, array{name: string, input: array}>}
     */
    public function converse(string $system, array $messages, array $tools, array $toolHandlers): array
    {
        $textParts = [];
        $toolCalls = [];

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $response = $this->send($system, $messages, $tools);
            $content = $response['content'] ?? [];

            foreach ($content as $block) {
                if (($block['type'] ?? null) === 'text') {
                    $textParts[] = $block['text'];
                }
            }

            if (($response['stop_reason'] ?? null) !== 'tool_use') {
                break;
            }

            $messages[] = ['role' => 'assistant', 'content' => $content];

            $toolResults = [];
            foreach ($content as $block) {
                if (($block['type'] ?? null) !== 'tool_use') {
                    continue;
                }

                $handler = $toolHandlers[$block['name']] ?? null;
                $result = $handler ? $handler($block['input'] ?? []) : ['error' => "Unknown tool: {$block['name']}"];

                $toolCalls[] = ['name' => $block['name'], 'input' => $block['input'] ?? [], 'result' => $result];
                $toolResults[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $block['id'],
                    'content' => json_encode($result),
                ];
            }

            $messages[] = ['role' => 'user', 'content' => $toolResults];
        }

        return ['text' => trim(implode("\n\n", $textParts)), 'tool_calls' => $toolCalls];
    }

    protected function send(string $system, array $messages, array $tools): array
    {
        try {
            $response = $this->client()->post(self::BASE_URL, array_filter([
                'model' => config('services.anthropic.model'),
                'max_tokens' => 1024,
                'system' => $system,
                'messages' => $messages,
                'tools' => $tools ?: null,
            ]))->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Anthropic API call failed: '.$e->response?->body(), previous: $e);
        }

        return $response->json();
    }
}
