<?php

namespace Tests\Feature;

use Tests\TestCase;

class SessionExpirationTest extends TestCase
{
    public function test_protected_route_without_session_redirects_to_login(): void
    {
        $response = $this->get('/panel');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_post_without_valid_csrf_redirects_to_login(): void
    {
        $response = $this->post('/logout', [
            '_token' => 'invalid-token',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_keep_alive_without_auth_redirects_to_login(): void
    {
        $response = $this->postJson('/session/keep-alive');

        $response->assertStatus(401);
        $response->assertJsonStructure(['message', 'redirect']);
    }

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_ajax_request_without_session_returns_json_with_redirect(): void
    {
        $response = $this->get('/panel', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure(['message', 'redirect']);
        $response->assertJson(['redirect' => route('login')]);
    }

    public function test_expired_session_clears_jwt_cookie_on_redirect(): void
    {
        $response = $this->withCookie('jwt_token', 'expired-token')
            ->get('/panel');

        $response->assertRedirect(route('login'));
        $response->assertCookieExpired('jwt_token');
    }
}