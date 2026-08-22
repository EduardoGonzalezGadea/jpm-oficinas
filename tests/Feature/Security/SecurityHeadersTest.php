<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    protected bool $requiresDatabase = false;

    public function test_middleware_agrega_x_frame_options(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_middleware_agrega_x_xss_protection(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    public function test_middleware_agrega_x_content_type_options(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_middleware_agrega_referrer_policy(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals('no-referrer-when-downgrade', $response->headers->get('Referrer-Policy'));
    }

    public function test_middleware_agrega_strict_transport_security(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals('max-age=31536000; includeSubDomains', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_middleware_agrega_content_security_policy(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_middleware_esta_disponible_en_el_contenedor(): void
    {
        $middleware = $this->app->make(SecurityHeadersMiddleware::class);
        $this->assertInstanceOf(SecurityHeadersMiddleware::class, $middleware);
    }
}
