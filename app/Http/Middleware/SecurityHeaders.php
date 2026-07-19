<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets baseline security response headers on every request (web + API).
 *
 * This is a real payment site (PayPal checkout, Sanctum session cookies), so
 * the defaults here are deliberately hardening: deny framing, block MIME
 * sniffing, and lock the CSP down to same-origin plus the exact third-party
 * domains the SPA actually needs — the PayPal JS SDK's script/iframe/API
 * hosts that `resources/js/App.jsx` (PayPalScriptProvider) and
 * `resources/js/pages/Checkout.jsx` (PayPalButtons) load, both the live
 * `www.paypal.com` and `www.sandbox.paypal.com` hosts since `PAYPAL_MODE`
 * can be either in different environments.
 *
 * Two things are deliberately *not* unconditional:
 *
 *  - Strict-Transport-Security is only sent when the request is already
 *    secure. Production sits behind a local nginx proxy that terminates TLS
 *    (see `trustProxies(at: '*')` in bootstrap/app.php), and `$request->secure()`
 *    correctly reflects that via X-Forwarded-Proto — so this fires in prod
 *    without ever telling a plain-HTTP local/dev client to upgrade.
 *  - The Content-Security-Policy is skipped in the `local` environment.
 *    Local development runs `npm run dev` (Vite's dev server on its own
 *    port, HMR over a websocket, unhashed module scripts fetched cross-port)
 *    alongside `php artisan serve` — a same-origin-only script-src would
 *    break that workflow for no security benefit (nothing sensitive is
 *    guarded by CSP on a developer's own machine). `testing` and
 *    `production` both serve the real `npm run build` output from the same
 *    origin as the app, so the policy applies there.
 */
class SecurityHeaders
{
    /**
     * PayPal hosts the SPA actually loads scripts/iframes/API calls from or
     * to. Both live and sandbox are allowed since PAYPAL_MODE differs across
     * environments and this list isn't request-specific.
     */
    private const PAYPAL_SCRIPT_HOSTS = [
        'https://www.paypal.com',
        'https://www.sandbox.paypal.com',
        'https://www.paypalobjects.com',
    ];

    private const PAYPAL_FRAME_HOSTS = [
        'https://www.paypal.com',
        'https://www.sandbox.paypal.com',
    ];

    private const PAYPAL_API_HOSTS = [
        'https://www.paypal.com',
        'https://www.sandbox.paypal.com',
        'https://api-m.paypal.com',
        'https://api-m.sandbox.paypal.com',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // The site doesn't use any of these device APIs itself; PayPal's own
        // iframed content runs in its own document and isn't affected by our
        // Permissions-Policy.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if (! app()->environment('local')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' ".implode(' ', self::PAYPAL_SCRIPT_HOSTS),
            // React's inline `style={{...}}` (e.g. GarmentMockup, StyleGuide swatches)
            // renders as inline `style` attributes, and PayPal's button widget also
            // injects inline styles into the containers it renders into.
            "style-src 'self' 'unsafe-inline'",
            // The admin-configurable site logo (Layout.jsx renders
            // settings.logo_url straight into an <img src>) can point at any
            // HTTPS host, not just this origin.
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            "connect-src 'self' ".implode(' ', self::PAYPAL_API_HOSTS),
            'frame-src '.implode(' ', self::PAYPAL_FRAME_HOSTS),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        return implode('; ', $directives);
    }
}
