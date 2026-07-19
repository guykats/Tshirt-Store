<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HealthController extends Controller
{
    /**
     * Lightweight, unauthenticated health check for external uptime monitoring
     * (UptimeRobot, Pingdom, a load balancer, etc.) and for the deploy pipeline
     * itself to confirm the app is actually serving after a release — a raw
     * HTTP 200 on "/" doesn't prove the DB connection works.
     *
     * Intentionally cheap: a single trivial query, no auth, and no internal
     * detail (query text, exception message/trace) leaked in the response —
     * only a generic per-check "ok"/"error" so a failure doesn't hand an
     * outside prober anything useful about the DB engine or schema.
     */
    public function show()
    {
        $checks = [
            'database' => $this->checkDatabase(),
        ];

        $healthy = ! in_array('error', $checks, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'error',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('select 1');

            return 'ok';
        } catch (Throwable $e) {
            // Log the real reason server-side; the response itself stays generic.
            Log::error('Health check: database connectivity failed', [
                'message' => $e->getMessage(),
            ]);

            return 'error';
        }
    }
}
