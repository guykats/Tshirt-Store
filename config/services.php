<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'),
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'client_secret' => env('PAYPAL_CLIENT_SECRET'),
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
    ],

    // deploy.yml runs `config:cache` on every deploy — if this key is added
    // directly to production .env outside of a deploy, it won't take effect
    // until the next push triggers a fresh config:cache.
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_CHAT_MODEL', 'claude-sonnet-5'),
    ],

    // Same config:cache gotcha as anthropic.api_key above. Needs a GitHub
    // personal access token (classic, `workflow` scope, or a fine-grained
    // token with "Actions: Read and write" on this repo) so the admin
    // enable/disable-automation control can call the Actions API.
    'github_actions' => [
        'token' => env('GITHUB_ACTIONS_TOKEN'),
        'owner' => env('GITHUB_ACTIONS_OWNER', 'guykats'),
        'repo' => env('GITHUB_ACTIONS_REPO', 'Tshirt-Store'),
        'workflow_file' => env('GITHUB_ACTIONS_PM_WORKFLOW', 'pm-agent.yml'),
    ],

];
