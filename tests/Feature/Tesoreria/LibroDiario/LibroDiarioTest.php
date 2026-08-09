<?php

namespace Tests\Feature\Tesoreria\LibroDiario;

use App\Livewire\Tesoreria\LibroDiario\Index;
use App\Models\User;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
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
    public function servicio_registra_asiento_salida_sin_saldo_previo_con_saldo_cero(): void
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
        // El saldo corre por identidad y nunca es negativo.
        $this->assertEquals(0, (float) $asiento->saldo);
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
        // Salida sin saldo previo en su identidad/subcuenta: queda en 0 (nunca negativo).
        $this->assertEquals(0, (float) $b->saldo);
    }

    /** @test */
    public function servicio_calcula_saldo_por_identidad(): void
    {
        $service = app(LibroDiarioService::class);

        $entradaA = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'identidad' => 'AAA',
        ]);

        $entradaB = $service->registrarAsiento([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500, 'identidad' => 'BBB',
        ]);

        $salidaB = $service->registrarAsiento([
            'fecha' => '2026-07-03', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 200, 'identidad' => 'BBB',
        ]);

        // Salida de una identidad sin saldo previo: 0, no consume el saldo de otra identidad.
        $salidaSinSaldo = $service->registrarAsiento([
            'fecha' => '2026-07-04', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 800, 'identidad' => 'CCC',
        ]);

        $this->assertEquals(1000, (float) $entradaA->saldo);
        $this->assertEquals(500, (float) $entradaB->saldo);
        $this->assertEquals(300, (float) $salidaB->saldo);
        $this->assertEquals(0, (float) $salidaSinSaldo->saldo);

        // El disponible de cada identidad es independiente.
        $this->assertEquals(1000, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'AAA'
        ));
        $this->assertEquals(300, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'BBB'
        ));
        $this->assertEquals(0, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'CCC'
        ));
        // Sin identidad = suma de todas las identidades.
        $this->assertEquals(1300, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id
        ));
    }

    /** @test */
    public function servicio_salida_con_base_de_distinta_identidad_primero_redistribuye(): void
    {
        $service = app(LibroDiarioService::class);

        $entrada = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'identidad' => 'AAA',
        ]);

        $salida = $service->registrarSalida([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 400, 'identidad' => 'BBB',
            'asociar' => $entrada->id,
        ]);

        // Se creó una redistribución previa (par salida/entrada) además de la salida.
        $grupoRedistribucion = LibroDiario::whereNotNull('grupo_redistribucion_id')->get();
        $this->assertCount(2, $grupoRedistribucion);

        // La salida quedó registrada en la identidad destino y sin asociar al asiento base.
        $this->assertEquals('BBB', $salida->identidad);
        $this->assertNull($salida->asociar);
        $this->assertEquals(-1, $salida->signo_efectivo);

        // El saldo del flujo origen bajó y el destino quedó neto en cero.
        $this->assertEquals(600, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'AAA'
        ));
        $this->assertEquals(0, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'BBB'
        ));
    }

    /** @test */
    public function servicio_salida_con_base_de_misma_identidad_no_redistribuye(): void
    {
        $service = app(LibroDiarioService::class);

        $entrada = $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'identidad' => 'AAA',
        ]);

        $salida = $service->registrarSalida([
            'fecha' => '2026-07-02', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 400, 'identidad' => 'AAA',
            'asociar' => $entrada->id,
        ]);

        $this->assertNull(LibroDiario::whereNotNull('grupo_redistribucion_id')->first());
        $this->assertEquals($entrada->id, $salida->asociar);
        $this->assertEquals(600, $service->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'AAA'
        ));
    }

    /** @test */
    public function servicio_salida_sin_base_no_redistribuye_y_queda_saldo_cero(): void
    {
        $service = app(LibroDiarioService::class);

        $salida = $service->registrarSalida([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoSalida->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        $this->assertNull(LibroDiario::whereNotNull('grupo_redistribucion_id')->first());
        $this->assertEquals(-1, $salida->signo_efectivo);
        $this->assertEquals(0, (float) $salida->saldo);
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
            'confirmado' => true, 'fecha_confirmacion' => now(),
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
    public function livewire_salida_con_base_de_distinta_identidad_redistribuye(): void
    {
        $this->actingAs($this->user, 'web');

        $entrada = LibroDiario::create([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'numero' => 1, 'signo_efectivo' => 1,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 1000, 'saldo' => 1000,
            'identidad' => 'AAA',
            'confirmado' => true, 'fecha_confirmacion' => now(),
        ]);

        Livewire::test(Index::class)
            ->set('fecha', '2026-07-15')
            ->set('tipo_id', $this->tipoSalida->id)
            ->set('concepto_id', $this->concepto->id)
            ->set('detalle_id', $this->detalle->id)
            ->set('asiento_base_id', $entrada->id)
            ->set('medio_id', $this->medio->id)
            ->set('monto', 400)
            ->set('identidad', 'BBB')
            ->set('denominacion', 'Empresa B')
            ->call('store')
            ->assertHasNoErrors();

        $this->assertEquals(2, LibroDiario::whereNotNull('grupo_redistribucion_id')->count());
        $this->assertDatabaseHas('tes_libro_diario', [
            'signo_efectivo' => -1,
            'identidad' => 'BBB',
            'monto' => 400,
        ]);
        $this->assertEquals(600, app(LibroDiarioService::class)->saldoActualFlujo(
            $this->medio->id, $this->concepto->id, $this->detalle->id, 'AAA'
        ));
    }

    /** @test */
    public function confirmar_un_miembro_de_redistribucion_confirma_el_par(): void
    {
        $service = app(LibroDiarioService::class);
        $destinoDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Destino']);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 2000,
        ]);

        [$origen, $destino] = $service->registrarRedistribucion([
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ], [
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $destinoDetalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        // Simular que ambos quedaron pendientes (datos previos o flujo de pendientes).
        LibroDiario::where('grupo_redistribucion_id', $origen->grupo_redistribucion_id)
            ->update(['confirmado' => false, 'fecha_confirmacion' => null]);

        $fecha = \Carbon\Carbon::parse('2026-07-15 10:30:00');

        // Confirmar la SALIDA del grupo: el otro (entrada) debe confirmarse igual.
        $service->toggleConfirmacion($origen->id, $fecha);
        $origen->refresh();
        $destino->refresh();

        $this->assertEquals($fecha, $origen->fecha_confirmacion, 'La salida debe quedar confirmada.');
        $this->assertEquals($fecha, $destino->fecha_confirmacion, 'La entrada asociada debe confirmarse con la misma fecha.');
        $this->assertTrue($origen->confirmado && $destino->confirmado);

        // Confirmar la entrada: el par debe recaer en el mismo estado.
        $origen->update(['confirmado' => false, 'fecha_confirmacion' => null]);
        $destino->update(['confirmado' => false, 'fecha_confirmacion' => null]);

        $service->toggleConfirmacion($destino->id, $fecha);
        $origen->refresh();
        $destino->refresh();

        $this->assertEquals($fecha, $origen->fecha_confirmacion);
        $this->assertEquals($fecha, $destino->fecha_confirmacion);
    }

    /** @test */
    public function desconfirmar_un_miembro_de_redistribucion_desconfirma_el_par(): void
    {
        $service = app(LibroDiarioService::class);
        $destinoDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Destino']);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 2000,
        ]);

        [$origen, $destino] = $service->registrarRedistribucion([
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ], [
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $destinoDetalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        // Ambos miembros quedan pendientes.
        $fecha = \Carbon\Carbon::parse('2026-07-02 09:00:00');
        $service->confirmarEntrada($destino->id);
        $destino->refresh();

        // Desconfirmar uno: el par debe quedar desconfirmado.
        $service->desconfirmarEntrada($destino->id);
        $origen->refresh();
        $destino->refresh();

        $this->assertNull($destino->fecha_confirmacion);
        $this->assertNull($origen->fecha_confirmacion);
        $this->assertFalse($origen->confirmado);
        $this->assertFalse($destino->confirmado);
    }

    /** @test */
    public function confirmar_solo_la_salida_de_una_redistribucion_no_lanza_excepcion(): void
    {
        $service = app(LibroDiarioService::class);
        $destinoDetalle = LbDetalle::create(['concepto_id' => $this->concepto->id, 'nombre' => 'Destino']);

        $service->registrarAsiento([
            'fecha' => '2026-07-01', 'tipo_id' => $this->tipoEntrada->id,
            'concepto_id' => $this->concepto->id, 'detalle_id' => $this->detalle->id,
            'medio_id' => $this->medio->id, 'monto' => 2000,
        ]);

        [$origen, $destino] = $service->registrarRedistribucion([
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $this->detalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ], [
            'fecha' => '2026-07-02', 'concepto_id' => $this->concepto->id,
            'detalle_id' => $destinoDetalle->id, 'medio_id' => $this->medio->id, 'monto' => 500,
        ]);

        LibroDiario::whereIn('id', [$origen->id, $destino->id])
            ->update(['confirmado' => false, 'fecha_confirmacion' => null]);

        $nuevoEstado = $service->toggleConfirmacion($origen->id, now());

        $this->assertTrue($nuevoEstado);
        $this->assertTrue(LibroDiario::find($origen->id)->confirmado);
        $this->assertTrue(LibroDiario::find($destino->id)->confirmado);
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
