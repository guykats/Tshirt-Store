<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Sanctum's EnsureFrontendRequestsAreStateful only starts the session for requests it
     * recognizes as coming from the SPA (Origin/Referer matching a configured stateful
     * domain). Test requests send neither by default, so every session-touching endpoint
     * (login/register/logout) would 500 with "Session store not set on request" without this.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Referer', config('app.url'));
    }
}
