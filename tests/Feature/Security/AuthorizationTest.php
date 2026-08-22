<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $requiresDatabase = false;

    public function test_ruta_panel_requiere_jwt(): void
    {
        $response = $this->get('/panel');

        $response->assertStatus(302);
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    public function test_ruta_tesoreria_requiere_jwt(): void
    {
        $response = $this->get('/tesoreria');

        $response->assertStatus(302);
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    public function test_ruta_logout_requiere_jwt(): void
    {
        $response = $this->post('/logout');

        $response->assertStatus(302);
        $this->assertStringContainsString('login', $response->headers->get('Location'));
    }

    public function test_api_cfe_requiere_jwt(): void
    {
        $response = $this->getJson('/api/cfe/pendientes');

        $response->assertStatus(401);
    }

    public function test_api_cfe_procesar_requiere_jwt(): void
    {
        $response = $this->postJson('/api/cfe/procesar');

        $response->assertStatus(401);
    }

    public function test_ruta_login_es_accesible_sin_auth(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
    }

    public function test_ruta_publica_acceso_publico_es_accesible(): void
    {
        $response = $this->get('/acceso-publico');

        $response->assertOk();
    }
}
