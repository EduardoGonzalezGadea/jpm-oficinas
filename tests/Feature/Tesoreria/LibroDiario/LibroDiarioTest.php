<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Http\Livewire\Tesoreria\LibroDiario\Index;
use App\Models\User;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbMedio;
use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Services\Tesoreria\LibroDiarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LibroDiarioTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private LbTipo $tipoEntrada;
    private LbTipo $tipoSalida;
    private LbTipo $tipoRedistribucion;
    private LbConcepto $concepto;
    private LbDetalle $detalle;
    private MedioDePago $medio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->tipoEntrada = LbTipo::create(['nombre' => 'Entrada', 'signo' => 1]);
        $this->tipoSalida = LbTipo::create(['nombre' => 'Salida', 'signo' => -1]);
        $this->tipoRedistribucion = LbTipo::create(['nombre' => 'Redistribución', 'signo' => 0]);
        $this->concepto = LbConcepto::create(['nombre' => 'Test Concepto']);
        $this->detalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Test Detalle']);
        $this->medio = MedioDePago::create([
            'nombre' => 'Test Medio',
            'nombre_corto' => 'TM',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);

        \Illuminate\Database\Eloquent\Model::unguarded(function () {
            LbMedio::create([
                'id' => $this->medio->id,
                'nombre' => 'Test Medio',
                'nombre_corto' => 'TM',
            ]);
        });
    }

    /** @test */
    public function servicio_registra_asiento_entrada(): void
    {
        $service = app(LibroDiarioService::class);

        $asiento = $service->registrarAsiento([
            'fecha' => '2026-07-01',
            'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 1000,
        ]);

        $this->assertDatabaseHas('tes_libro_diario', [
            'id' => $asiento->id,
            'numero' => 1,
            'signo_efectivo' => 1,
            'monto' => 1000,
            'saldo' => 1000,
        ]);
    }

    /** @test */
    public function servicio_registra_asiento_salida_con_saldo_negativo(): void
    {
        $service = app(LibroDiarioService::class);

        $asiento = $service->registrarAsiento([
            'fecha' => '2026-07-01',
            'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id,
            'monto' => 500,
        ]);

        $this->assertEquals(-1, $asiento->signo_efectivo);
        $this->assertEquals(-500, (float) $asiento->saldo);
    }

    /** @test */
    public function servicio_acumula_saldo_en_misma_subcuenta(): void
    {
        $service = app(LibroDiarioService::class);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000,
        ]);

        $segundo = $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        $this->assertEquals(1500, (float) $segundo->saldo);
    }

    /** @test */
    public function servicio_saldos_independientes_por_subcuenta(): void
    {
        $service = app(LibroDiarioService::class);
        $otroDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Otro Detalle']);

        $a = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000,
        ]);

        $b = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $otroDetalle->id,
            'medio_id' => $this->medio->id, 'monto' => 300,
        ]);

        $this->assertEquals(1000, (float) $a->saldo);
        $this->assertEquals(-300, (float) $b->saldo);
    }

    /** @test */
    public function servicio_registra_redistribucion(): void
    {
        $service = app(LibroDiarioService::class);
        $destinoDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Destino']);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 2000,
        ]);

        [$origen, $destino] = $service->registrarRedistribucion(
            [
                'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
                'detalle_id' => $this->detalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
            ],
            [
                'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
                'detalle_id' => $destinoDetalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
                'identidad' => '12345678', 'denominacion' => 'Fulano',
            ]
        );

        $this->assertEquals(-1, $origen->signo_efectivo);
        $this->assertEquals(1500, (float) $origen->saldo);
        $this->assertEquals(1, $destino->signo_efectivo);
        $this->assertEquals(500, (float) $destino->saldo);
        $this->assertEquals($origen->id, $destino->asociar);
        $this->assertEquals('12345678', $destino->identidad);
        $this->assertEquals('FULANO', $destino->denominacion);
    }

    /** @test */
    public function servicio_lista_solo_el_ultimo_asiento_base_con_saldo_disponible(): void
    {
        $service = app(LibroDiarioService::class);

        $entrada = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000,
        ]);

        $ultimo = $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 400,
        ]);

        $bases = $service->listarAsientosBaseDisponibles($this->concepto->id, $this->detalle->id);

        $this->assertCount(1, $bases);
        $this->assertEquals($ultimo->id, $bases->first()->id);
        $this->assertEquals(600, (float) $bases->first()->saldo);
        $this->assertNotEquals($entrada->id, $bases->first()->id);
    }

    /** @test */
    public function servicio_informa_el_saldo_actual_del_flujo_completo(): void
    {
        $service = app(LibroDiarioService::class);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1200,
        ]);
        $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 450,
        ]);

        $flujo = $service->saldosActualesPorFlujo([
            'medio_id' => $this->medio->id,
            'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id,
        ])->first();

        $this->assertEquals(750, (float) $flujo->saldo_actual);
        $this->assertEquals(750, $service->saldoActualFlujo(
            $this->medio->id,
            $this->concepto->id,
            $this->detalle->id
        ));
    }

    /** @test */
    public function servicio_no_permite_redistribuir_mas_del_saldo_actual_del_flujo(): void
    {
        $service = app(LibroDiarioService::class);
        $destinoDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Destino sin saldo']);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 200,
        ]);

        $this->expectException(\DomainException::class);

        $service->registrarRedistribucion([
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id, 'medio_id' => $this->medio->id, 'monto' => 201,
        ], [
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $destinoDetalle->id, 'medio_id' => $this->medio->id, 'monto' => 201,
        ]);
    }

    /** @test */
    public function servicio_elimina_asiento_y_recalcula_saldos(): void
    {
        $service = app(LibroDiarioService::class);

        $a = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000,
        ]);
        $b = $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        $service->eliminarAsiento($a->id);

        $b->refresh();
        $this->assertEquals(500, (float) $b->saldo);
        $this->assertSoftDeleted('tes_libro_diario', ['id' => $a->id]);
    }

    /** @test */
    public function servicio_actualiza_solo_campos_no_financieros(): void
    {
        $service = app(LibroDiarioService::class);

        $asiento = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000,
        ]);

        $actualizado = $service->actualizarCamposNoFinancieros($asiento->id, [
            'identidad' => '5.678.901-2',
            'denominacion' => 'Juan Pérez',
            'monto' => 9999,
        ]);

        $this->assertEquals('5.678.901-2', $actualizado->identidad);
        $this->assertEquals('JUAN PÉREZ', $actualizado->denominacion);
        $this->assertEquals(1000, (float) $actualizado->monto);
    }

    /** @test */
    public function numeracion_secuencial_por_anio(): void
    {
        $service = app(LibroDiarioService::class);

        $a = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 100,
        ]);
        $b = $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 200,
        ]);
        $c = $service->registrarAsiento([
            'fecha' => '2027-01-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 300,
        ]);

        $this->assertEquals(1, $a->numero);
        $this->assertEquals(2, $b->numero);
        $this->assertEquals(1, $c->numero);
    }

    /** @test */
    public function livewire_componente_muestra_listado(): void
    {
        $this->actingAs($this->user, 'web');

        LibroDiario::create([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'numero' => 1, 'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'saldo' => 1000,
        ]);

        Livewire::test(Index::class)
            ->assertSee('1.000,00')
            ->assertSee('Test Concepto')
            ->assertSee('Test Medio');
    }

    /** @test */
    public function livewire_puede_crear_asiento(): void
    {
        $this->actingAs($this->user, 'web');

        Livewire::test(Index::class)
            ->set('fecha', '2026-07-15')
            ->set('tipo_id', $this->tipoEntrada->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->set('monto', 2500)
            ->set('identidad', '3.456.789-0')
            ->set('denominacion', 'Empresa S.A.')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tes_libro_diario', [
            'numero' => 1,
            'monto' => 2500,
            'saldo' => 2500,
            'identidad' => '3.456.789-0',
            'denominacion' => 'Empresa S.A.',
        ]);
    }

    /** @test */
    public function livewire_valida_monto_requerido(): void
    {
        $this->actingAs($this->user, 'web');

        Livewire::test(Index::class)
            ->set('fecha', '2026-07-15')
            ->set('tipo_id', $this->tipoEntrada->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('medio_id', $this->medio->id)
            ->set('monto', null)
            ->call('store')
            ->assertHasErrors('monto');
    }
}
