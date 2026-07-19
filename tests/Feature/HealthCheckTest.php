<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_returns_ok_with_expected_shape_when_db_is_reachable(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
        $response->assertExactJson([
            'status' => 'ok',
            'checks' => [
                'database' => 'ok',
            ],
        ]);
    }

    public function test_health_endpoint_requires_no_authentication(): void
    {
        // No Sanctum session/user set up at all — should still respond.
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    public function test_health_endpoint_returns_503_without_leaking_details_when_db_check_fails(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andThrow(new \RuntimeException('SQLSTATE[HY000] Connection refused on some.internal.host:3306'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503);
        $response->assertExactJson([
            'status' => 'error',
            'checks' => [
                'database' => 'error',
            ],
        ]);

        // The generic error string must not leak the underlying exception
        // message (hostname, port, driver detail, etc.) to the client.
        $response->assertDontSeeText('Connection refused', escape: false);
        $response->assertDontSeeText('3306', escape: false);
    }
}
