<?php

namespace Tests\Feature\Tesoreria;

use App\Models\TesCfePendiente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CfeControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_pendientes_returns_json_list(): void
    {
        TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        TesCfePendiente::create([
            'tipo_cfe'        => 'certificado_residencia',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $response = $this->getJson('/api/cfe/pendientes');

        $response->assertStatus(200);
        $response->assertJsonCount(2);
    }

    public function test_pendientes_returns_empty_when_none(): void
    {
        $response = $this->getJson('/api/cfe/pendientes');

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_rechazar_cfe_returns_success(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $response = $this->postJson("/api/cfe/{$pendiente->id}/rechazar", [
            'motivo' => 'Documento incorrecto',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertEquals('rechazado', $pendiente->fresh()->estado);
        $this->assertEquals('Documento incorrecto', $pendiente->fresh()->motivo_rechazo);
    }

    public function test_rechazar_cfe_returns_404_when_not_found(): void
    {
        $response = $this->postJson('/api/cfe/9999/rechazar', [
            'motivo' => 'test',
        ]);

        $response->assertStatus(404);
    }

    public function test_rechazar_cfe_returns_404_when_not_pendiente(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'datos_extraidos' => [],
            'estado'          => 'confirmado',
        ]);

        $response = $this->postJson("/api/cfe/{$pendiente->id}/rechazar", [
            'motivo' => 'test',
        ]);

        $response->assertStatus(404);
    }

    public function test_analizar_cfe_returns_error_when_file_not_found(): void
    {
        $response = $this->postJson('/api/cfe/analizar', [
            'filepath' => '/nonexistent/path.pdf',
        ]);

        $response->assertJson(['es_cfe' => false]);
    }

    public function test_analizar_cfe_returns_error_when_no_filepath(): void
    {
        $response = $this->postJson('/api/cfe/analizar', []);

        $response->assertJson(['es_cfe' => false]);
    }

    public function test_crear_registro_returns_error_for_unknown_type(): void
    {
        $response = $this->postJson('/api/cfe/crear-registro', [
            'tipo_cfe' => 'tipo_inexistente',
            'datos'    => [],
        ]);

        $response->assertJson(['success' => false]);
    }

    public function test_crear_registro_returns_redirect_for_multas(): void
    {
        $response = $this->postJson('/api/cfe/crear-registro', [
            'tipo_cfe' => 'multas_cobradas',
            'datos'    => ['serie' => 'A', 'numero' => '12345'],
        ]);

        $response->assertJson([
            'success' => true,
            'tipo_cfe' => 'multas_cobradas',
        ]);
        $response->assertJsonStructure(['redirect_url']);
    }
}
