<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        // First scheduled job in the project. Laravel's scheduler only decides
        // *when* things run; something still has to invoke `schedule:run` every
        // minute for that to matter in production — deploy.yml wires that up via
        // an idempotent crontab entry on the Hostinger host.
        $schedule->command('app:backup-database')->dailyAt('03:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Production runs behind a local reverse proxy (nginx -> PHP-FPM) that terminates
        // TLS, so without this the app sees every request as plain HTTP: $request->secure()
        // is false, generated URLs come back http://, and the session cookie never gets
        // marked Secure. '*' is safe here since the only thing that can set these headers
        // is the proxy on the same box, not an untrusted network hop.
        $middleware->trustProxies(at: '*');

        // Runs on every request (web + API) so CSP/HSTS/X-Frame-Options etc.
        // land on API JSON responses too, not just the SPA shell.
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
