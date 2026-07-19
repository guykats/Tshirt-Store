<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_baseline_security_headers_are_present_on_a_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_security_headers_are_also_present_on_api_responses(): void
    {
        $response = $this->getJson('/api/site-settings');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_csp_allows_the_paypal_domains_checkout_actually_loads(): void
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('https://www.paypal.com', $csp);
        $this->assertStringContainsString('https://www.sandbox.paypal.com', $csp);
        $this->assertStringContainsString('https://www.paypalobjects.com', $csp);

        // self-hosted Vite build assets must still be allowed.
        $this->assertStringContainsString("script-src 'self'", $csp);
    }

    public function test_hsts_is_only_sent_over_a_secure_request(): void
    {
        $plain = $this->get('/');
        $plain->assertHeaderMissing('Strict-Transport-Security');

        // Production sits behind a proxy trusted via TrustProxies::at('*'), so
        // this mirrors how nginx tells Laravel the original request was HTTPS.
        $secure = $this->get('/', ['X-Forwarded-Proto' => 'https']);
        $secure->assertHeader('Strict-Transport-Security');
    }
}
