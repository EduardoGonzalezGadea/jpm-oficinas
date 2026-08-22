<?php

namespace Tests\Feature\Tesoreria\CajaDiaria;

use App\Livewire\Tesoreria\CajaDiaria\Arqueo;
use App\Models\User;
use App\Models\Tesoreria\TesDiscriminacionMonetaria;
use App\Models\Tesoreria\Cajas\CajaApertura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArqueoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private TesDiscriminacionMonetaria $bil;
    private TesDiscriminacionMonetaria $moneda;
    private CajaApertura $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->bil = TesDiscriminacionMonetaria::create([
            'tipo' => 'Billetes',
            'valor' => 1000,
            'texto' => 'Billete de 1000',
            'activo' => true,
        ]);

        $this->moneda = TesDiscriminacionMonetaria::create([
            'tipo' => 'Monedas',
            'valor' => 1,
            'texto' => 'Moneda de 1',
            'activo' => true,
        ]);

        $this->caja = CajaApertura::create([
            'cajero_id' => $this->user->id,
            'fecha_apertura' => today()->format('Y-m-d'),
            'hora_apertura' => '08:00',
            'saldo_inicial' => 5000,
            'estado' => 'abierta',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function al_cambiar_cantidad_en_arqueo_recalcula_total_de_fila_total_efectivo_y_diferencia(): void
    {
        $this->actingAs($this->user, 'web');

        Livewire::actingAs($this->user)
            ->test(Arqueo::class)
            ->set('modo_calculo', 'cantidad')
            ->set('desglose.' . $this->bil->id . '.cantidad', 5)
            ->assertSet('desglose.' . $this->bil->id . '.total', 5000)
            ->assertSet('total_efectivo', 5000)
            ->assertSet('diferencia', 0)
            ->set('desglose.' . $this->bil->id . '.cantidad', 6)
            ->assertSet('desglose.' . $this->bil->id . '.total', 6000)
            ->assertSet('total_efectivo', 6000)
            ->assertSet('diferencia', 1000);
    }

    /** @test */
    public function al_cambiar_total_en_arqueo_recalcula_cantidad_total_efectivo_y_diferencia(): void
    {
        $this->actingAs($this->user, 'web');

        Livewire::actingAs($this->user)
            ->test(Arqueo::class)
            ->set('modo_calculo', 'total')
            ->set('desglose.' . $this->bil->id . '.total', 4000)
            ->assertSet('desglose.' . $this->bil->id . '.cantidad', 4)
            ->assertSet('total_efectivo', 4000)
            ->assertSet('diferencia', -1000);
    }
}
