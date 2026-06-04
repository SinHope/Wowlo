<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Baseline browser-security headers on every web response (SECURITY.md §7).
     *
     * Implemented as middleware (not nginx) so it's portable across any host
     * and covered by tests. A strict Content-Security-Policy is intentionally
     * NOT set yet — the landing page loads external scripts (Spline, Bunny
     * fonts) and inline Alpine, so a CSP needs nonce work first. Tracked as a
     * later hardening step.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');                       // anti-clickjacking
        $response->headers->set('X-Content-Type-Options', 'nosniff');                   // no MIME sniffing
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
