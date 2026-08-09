<?php

namespace Tests\Feature\Tesoreria\CajaDiaria;

use App\Livewire\Tesoreria\CajaDiaria\Movimientos;
use App\Models\User;
use App\Models\Tesoreria\Cajas\CajaApertura;
use App\Models\Tesoreria\Cajas\CajaMovimiento;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovimientosTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LbTipo $tipoEntrada;
    private LbTipo $tipoSalida;
    private LbConcepto $concepto;
    private LbDetalle $detalle;
    private MedioDePago $medio;
    private CajaApertura $caja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->tipoEntrada = LbTipo::create(['nombre' => 'Entrada', 'signo' => 1]);
        $this->tipoSalida = LbTipo::create(['nombre' => 'Salida', 'signo' => -1]);
        $this->concepto = LbConcepto::create(['nombre' => 'Test Concepto']);
        $this->detalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Test Detalle']);
        $this->medio = MedioDePago::create([
            'nombre' => 'Test Medio',
            'nombre_corto' => 'TM',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);

        $this->caja = CajaApertura::create([
            'cajero_id' => $this->user->id,
            'fecha_apertura' => '2026-07-01',
            'hora_apertura' => '08:00',
            'saldo_inicial' => 0,
            'estado' => 'abierta',
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function livewire_muestra_asientos_base_disponibles_para_salida(): void
    {
        $this->actingAs($this->user, 'web');

        LibroDiario::create([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'numero' => 1, 'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'saldo' => 1000,
        ]);

        Livewire::test(Movimientos::class)
            ->set('tipo_id', $this->tipoSalida->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->assertHasNoErrors()
            ->assertSet('asientos_base', function (array $asientos) {
                return count($asientos) === 1;
            });
    }

    /** @test */
    public function livewire_salida_con_base_asocia_directamente_el_asiento(): void
    {
        $this->actingAs($this->user, 'web');

        $entrada = app(LibroDiarioService::class)->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'identidad' => 'AAA',
        ]);

        Livewire::test(Movimientos::class)
            ->set('tipo_id', $this->tipoSalida->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->set('asiento_base_id', $entrada->id)
            ->set('monto', 400)
            ->call('registrarMovimiento')
            ->assertHasNoErrors();

        $salida = LibroDiario::where('signo_efectivo', -1)->where('monto', 400)->first();
        $this->assertNotNull($salida);
        $this->assertEquals($entrada->id, $salida->asociar);

        $this->assertDatabaseHas('tes_cajas_movimientos', [
            'caja_apertura_id' => $this->caja->id,
            'tipo_movimiento' => 'EGRESO',
            'monto' => 400,
            'libro_diario_id' => $salida->id,
        ]);

        $this->assertEquals(600, app(LibroDiarioService::class)->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'AAA'
        ));
    }

    /** @test */
    public function livewire_rechaza_monto_mayor_al_saldo_del_asiento_base(): void
    {
        $this->actingAs($this->user, 'web');

        $entrada = app(LibroDiarioService::class)->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500, 'identidad' => 'AAA',
        ]);

        Livewire::test(Movimientos::class)
            ->set('tipo_id', $this->tipoSalida->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->set('asiento_base_id', $entrada->id)
            ->set('monto', 700)
            ->call('registrarMovimiento')
            ->assertHasErrors('monto');

        $this->assertDatabaseCount('tes_cajas_movimientos', 0);
    }

    /** @test */
    public function livewire_no_permite_base_para_entrada(): void
    {
        $this->actingAs($this->user, 'web');

        $entrada = app(LibroDiarioService::class)->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        Livewire::test(Movimientos::class)
            ->set('tipo_id', $this->tipoEntrada->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->set('asiento_base_id', $entrada->id)
            ->set('monto', 700)
            ->call('registrarMovimiento')
            ->assertHasErrors('asiento_base_id');

        $this->assertDatabaseCount('tes_cajas_movimientos', 0);
    }

    /** @test */
    public function livewire_solo_muestra_asientos_base_del_medio_seleccionado(): void
    {
        $this->actingAs($this->user, 'web');

        $otroMedio = MedioDePago::create([
            'nombre' => 'Otro Medio',
            'nombre_corto' => 'OM',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);

        app(LibroDiarioService::class)->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $otroMedio->id, 'monto' => 1000, 'identidad' => 'AAA',
        ]);

        Livewire::test(Movimientos::class)
            ->set('tipo_id', $this->tipoSalida->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->assertHasNoErrors()
            ->assertSet('asientos_base', []);
    }
}
