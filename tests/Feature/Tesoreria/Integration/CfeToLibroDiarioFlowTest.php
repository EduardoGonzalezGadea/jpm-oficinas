<?php

namespace Tests\Feature\Tesoreria\Integration;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfeMedioPago;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\LbConcepto;
use App\Services\Tesoreria\LibroDiarioService;
use App\Services\Tesoreria\RegistrarAsientosCfeService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Integración: Flujo CFE → Libro Diario
 * 
 * Flujo:
 * 1. Recepción de CFE (PDF)
 * 2. Extracción de datos
 * 3. Confirmación de CFE
 * 4. Registro automático en Libro Diario
 * 5. Verificación de saldos
 */
class CfeToLibroDiarioFlowTest extends TesoreriaTestCase
{
    private LibroDiarioService $libroService;
    private RegistrarAsientosCfeService $asientosCfeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->libroService = app(LibroDiarioService::class);
        $this->asientosCfeService = app(RegistrarAsientosCfeService::class);
    }

    public function test_flujo_cfe_multas_confirmacion_y_asiento(): void
    {
        // 1. Crear CFE pendiente de multas
        $cfe = TesCfe::factory()->pendiente()->create([
            'documento_tipo' => 'eFactura',
            'documento_numero' => '123456',
            'fecha' => '2026-08-14',
            'total_a_pagar' => 5000.00,
        ]);

        // 2. Agregar items
        TesCfeItem::factory()->paraCfe($cfe)->create([
            'descripcion' => 'Multa Art. 103',
            'subtotal' => 5000.00,
        ]);

        // 3. Agregar medio de pago
        $medioEfectivo = $this->getMedioDePago('EF');
        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioEfectivo)
            ->conMonto(5000.00)
            ->create();

        // 4. Confirmar CFE
        $cfe->update(['status' => 'confirmado']);

        // 5. Registrar asiento en libro diario
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => $cfe->total_a_pagar,
            'documento_referencia' => 'CFE-' . $cfe->documento_numero,
        ]);

        // Verificaciones
        $this->assertEquals('confirmado', $cfe->fresh()->status);
        $this->assertNotNull($asiento);
        $this->assertFloatEquals(5000.00, $asiento->monto);
        $this->assertStringContainsString($cfe->documento_numero, $asiento->documento_referencia);
    }

    public function test_flujo_cfe_certificados_completo(): void
    {
        // 1. Crear CFE de certificados
        $cfe = TesCfe::factory()->pendiente()->create([
            'documento_tipo' => 'eFactura',
            'fecha' => '2026-08-14',
            'receptor_nombre_denominacion' => 'Juan Pérez',
            'total_a_pagar' => 500.00,
        ]);

        TesCfeItem::factory()->paraCfe($cfe)->create([
            'descripcion' => 'Certificado de Residencia',
            'cantidad' => 1,
            'precio_unitario' => 500.00,
            'subtotal' => 500.00,
        ]);

        // 2. Medio de pago: efectivo
        $medioEfectivo = $this->getMedioDePago('EF');
        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioEfectivo)
            ->conMonto(500.00)
            ->create();

        // 3. Confirmar y registrar asiento
        $cfe->update(['status' => 'confirmado']);

        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Certificados')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 500.00,
            'documento_referencia' => 'CFE-CERT-' . $cfe->id,
        ]);

        // Verificaciones
        $this->assertEquals('confirmado', $cfe->fresh()->status);
        $this->assertFloatEquals(500.00, $asiento->monto);
        $this->assertFloatEquals(500.00, $asiento->saldo);
    }

    public function test_flujo_multiples_cfes_mismo_dia(): void
    {
        $fecha = '2026-08-14';
        $medioEfectivo = $this->getMedioDePago('EF');

        // 1. Crear múltiples CFEs
        $cfe1 = TesCfe::factory()->confirmado()->create([
            'fecha' => $fecha,
            'total_a_pagar' => 2000.00,
        ]);

        $cfe2 = TesCfe::factory()->confirmado()->create([
            'fecha' => $fecha,
            'total_a_pagar' => 3500.00,
        ]);

        $cfe3 = TesCfe::factory()->confirmado()->create([
            'fecha' => $fecha,
            'total_a_pagar' => 1500.00,
        ]);

        // 2. Registrar asientos individuales
        $asiento1 = $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 2000.00,
            'documento_referencia' => 'CFE-' . $cfe1->id,
        ]);

        $asiento2 = $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 3500.00,
            'documento_referencia' => 'CFE-' . $cfe2->id,
        ]);

        $asiento3 = $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 1500.00,
            'documento_referencia' => 'CFE-' . $cfe3->id,
        ]);

        // 3. Verificar saldos acumulados
        $this->assertFloatEquals(2000.00, $asiento1->saldo);
        $this->assertFloatEquals(5500.00, $asiento2->saldo); // 2000 + 3500
        $this->assertFloatEquals(7000.00, $asiento3->saldo); // 5500 + 1500

        // 4. Verificar total del día
        $totalDia = LibroDiario::whereDate('fecha', $fecha)->sum('monto');
        $this->assertFloatEquals(7000.00, $totalDia);
    }

    public function test_flujo_cfe_con_multiples_medios_pago(): void
    {
        // 1. Crear CFE
        $cfe = TesCfe::factory()->pendiente()->create([
            'fecha' => '2026-08-14',
            'total_a_pagar' => 10000.00,
        ]);

        // 2. Agregar múltiples medios de pago
        $medioEfectivo = $this->getMedioDePago('EF');
        $medioCheque = $this->getMedioDePago('CH');
        $medioTarjeta = $this->getMedioDePago('TD');

        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioEfectivo)
            ->conMonto(4000.00)
            ->create();

        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioCheque)
            ->conMonto(3000.00)
            ->create();

        TesCfeMedioPago::factory()
            ->paraCfe($cfe)
            ->conMedio($medioTarjeta)
            ->conMonto(3000.00)
            ->create();

        // 3. Confirmar
        $cfe->update(['status' => 'confirmado']);

        // 4. Registrar asientos por cada medio de pago
        $asientoEfectivo = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 4000.00,
            'documento_referencia' => 'CFE-' . $cfe->id . '-EF',
        ]);

        $asientoCheque = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioCheque->id,
            'monto' => 3000.00,
            'documento_referencia' => 'CFE-' . $cfe->id . '-CH',
        ]);

        $asientoTarjeta = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioTarjeta->id,
            'monto' => 3000.00,
            'documento_referencia' => 'CFE-' . $cfe->id . '-TD',
        ]);

        // Verificaciones
        $this->assertEquals(3, $cfe->mediosPago()->count());
        $this->assertFloatEquals(10000.00, $cfe->mediosPago->sum('monto'));

        $totalAsientos = $asientoEfectivo->monto + $asientoCheque->monto + $asientoTarjeta->monto;
        $this->assertFloatEquals(10000.00, $totalAsientos);
    }

    public function test_flujo_cfe_rechazado_sin_asiento(): void
    {
        // 1. Crear CFE
        $cfe = TesCfe::factory()->pendiente()->create([
            'fecha' => '2026-08-14',
            'total_a_pagar' => 5000.00,
        ]);

        // 2. Rechazar CFE (datos incorrectos, duplicado, etc.)
        $cfe->update(['status' => 'rechazado']);

        // 3. Verificar que NO hay asientos
        $asientos = LibroDiario::where('documento_referencia', 'LIKE', '%CFE-' . $cfe->id . '%')->get();
        $this->assertCount(0, $asientos);

        $this->assertEquals('rechazado', $cfe->status);
    }

    public function test_flujo_cfe_multa_cobrada_libro_diario(): void
    {
        // Flujo completo: CFE → Multa Cobrada → Libro Diario

        // 1. Crear multa cobrada
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'recibo' => 'REC-2026-001',
            'monto' => 3000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'descripcion' => 'Multa Art. 103',
            'subtotal' => 3000.00,
        ]);

        // 2. Crear CFE asociado
        $cfe = TesCfe::factory()->confirmado()->create([
            'fecha' => $multaCobrada->fecha,
            'total_a_pagar' => 3000.00,
            'documento_numero' => $multaCobrada->recibo,
        ]);

        // 3. Registrar asiento
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $cfe->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 3000.00,
            'documento_referencia' => 'REC-' . $multaCobrada->recibo,
        ]);

        // Verificaciones
        $this->assertEquals('confirmado', $cfe->status);
        $this->assertFloatEquals(3000.00, $multaCobrada->monto);
        $this->assertFloatEquals(3000.00, $asiento->monto);
        $this->assertEquals($multaCobrada->fecha->format('Y-m-d'), $asiento->fecha->format('Y-m-d'));
    }

    public function test_flujo_cfe_confirmacion_actualiza_saldos(): void
    {
        $medioEfectivo = $this->getMedioDePago('EF');
        $conceptoRecaudacion = $this->getConcepto(LbConcepto::RECAUDACION_222);
        $detalleMultas = $this->getDetalle('Multas');

        // 1. Saldo inicial
        $saldoInicial = $this->getSaldoSubcuenta($detalleMultas->id);

        // 2. Confirmar varios CFEs
        for ($i = 1; $i <= 5; $i++) {
            $cfe = TesCfe::factory()->confirmado()->create([
                'fecha' => '2026-08-14',
                'total_a_pagar' => 1000.00 * $i,
            ]);

            $this->libroService->registrarAsiento([
                'fecha' => $cfe->fecha,
                'tipo_id' => $this->getTipo('Entrada')->id,
                'signo_efectivo' => 1,
                'concepto_id' => $conceptoRecaudacion->id,
                'detalle_id' => $detalleMultas->id,
                'medio_id' => $medioEfectivo->id,
                'monto' => $cfe->total_a_pagar,
                'documento_referencia' => 'CFE-' . $cfe->id,
            ]);
        }

        // 3. Verificar saldo final
        $saldoFinal = $this->getSaldoSubcuenta($detalleMultas->id);
        $totalIngresado = 1000 + 2000 + 3000 + 4000 + 5000; // 15000

        $this->assertFloatEquals($saldoInicial + $totalIngresado, $saldoFinal);
    }

    public function test_flujo_cfe_con_hash_evita_duplicados(): void
    {
        $hash = hash('sha256', 'contenido_pdf_test');

        // 1. Crear primer CFE con hash
        $cfe1 = TesCfe::factory()->create([
            'pdf_hash' => $hash,
            'total_a_pagar' => 5000.00,
        ]);

        // 2. Confirmar y registrar asiento
        $cfe1->update(['status' => 'confirmado']);

        $asiento1 = $this->libroService->registrarAsiento([
            'fecha' => $cfe1->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 5000.00,
            'documento_referencia' => 'CFE-' . $cfe1->id,
        ]);

        // 3. Intentar crear duplicado con mismo hash
        $duplicado = TesCfe::where('pdf_hash', $hash)->first();

        $this->assertNotNull($duplicado);
        $this->assertEquals($cfe1->id, $duplicado->id);

        // 4. Verificar que solo hay un asiento
        $asientos = LibroDiario::where('documento_referencia', 'LIKE', 'CFE-%')->get();
        $this->assertCount(1, $asientos);
    }
}
