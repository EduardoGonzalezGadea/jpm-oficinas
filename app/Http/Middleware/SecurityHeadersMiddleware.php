<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que añade headers de seguridad HTTP a todas las respuestas
 * para mitigar ataques comunes: XSS, clickjacking, MIME-sniffing, etc.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY', false);
        $response->headers->set('X-XSS-Protection', '1; mode=block', false);
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade', false);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:", false);

        return $response;
    }
}
