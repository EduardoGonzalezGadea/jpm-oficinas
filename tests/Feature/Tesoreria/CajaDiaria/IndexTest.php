<?php

namespace Tests\Feature\Tesoreria\CajaDiaria;

use App\Livewire\Tesoreria\CajaDiaria\Index;
use App\Models\User;
use App\Models\Tesoreria\Cajas\CajaApertura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'web');
    }

    private function crearCaja(array $atributos = []): CajaApertura
    {
        return CajaApertura::create(array_merge([
            'cajero_id' => $this->user->id,
            'fecha_apertura' => today()->format('Y-m-d'),
            'hora_apertura' => '08:00',
            'saldo_inicial' => 0,
            'estado' => 'abierta',
            'created_by' => $this->user->id,
        ], $atributos));
    }

    /** @test */
    public function mi_caja_muestra_la_caja_abierta_aunque_haya_una_cerrada_el_mismo_dia(): void
    {
        $cerrada = $this->crearCaja([
            'saldo_inicial' => 1000,
            'estado' => 'cerrada',
            'saldo_cierre' => 1000,
        ]);

        $abierta = $this->crearCaja([
            'saldo_inicial' => 500,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSet('cajaTrabajo.id', $abierta->id);

        $this->assertSame('cerrada', $cerrada->fresh()->estado);
    }

    /** @test */
    public function mi_caja_queda_vacia_cuando_no_hay_caja_abierta_del_usuario(): void
    {
        $this->crearCaja([
            'saldo_inicial' => 1000,
            'estado' => 'cerrada',
            'saldo_cierre' => 1000,
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSet('cajaTrabajo', null)
            ->assertSet('tab', 'cajas');
    }

    /** @test */
    public function mi_caja_muestra_la_caja_abierta_aunque_se_haya_abierto_en_un_dia_anterior(): void
    {
        $abierta = $this->crearCaja([
            'fecha_apertura' => now()->subDay()->format('Y-m-d'),
        ]);

        Livewire::actingAs($this->user)
            ->test(Index::class)
            ->assertSet('cajaTrabajo.id', $abierta->id)
            ->assertSet('tab', 'mi-caja');
    }
}
