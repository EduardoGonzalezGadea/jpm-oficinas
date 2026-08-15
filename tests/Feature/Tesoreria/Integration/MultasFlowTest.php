<?php

namespace Tests\Feature\Tesoreria\Integration;

use App\Models\Tesoreria\Multa;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\LbConcepto;
use App\Services\Tesoreria\LibroDiarioService;
use Tests\TesoreriaTestCase;

/**
 * Tests de Integración: Flujo Completo de Multas
 * 
 * Flujo:
 * 1. Registro de multa en catálogo
 * 2. Cobro de multa (contado/crédito)
 * 3. Registro en Libro Diario
 * 4. Verificación de saldos
 */
class MultasFlowTest extends TesoreriaTestCase
{
    private LibroDiarioService $libroService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->libroService = app(LibroDiarioService::class);
    }

    public function test_flujo_multa_cobro_contado_libro_diario(): void
    {
        // 1. Crear multa en catálogo
        $multa = Multa::factory()->enPesos()->create([
            'codigo' => 'M001',
            'articulo' => '103',
            'descripcion' => 'Exceso de velocidad',
            'importe_original' => 5000.00,
        ]);

        // 2. Cobrar multa (contado)
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'recibo' => 'REC-2026-001',
            'cedula' => '12345678',
            'nombre' => 'Juan Pérez',
            'monto' => 5000.00,
        ]);

        // 3. Agregar item de multa
        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa->codigo,
            'descripcion' => $multa->descripcion,
            'subtotal' => 5000.00,
        ]);

        // 4. Registrar asiento en libro diario
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 5000.00,
            'documento_referencia' => 'MULTA-' . $multaCobrada->recibo,
        ]);

        // Verificaciones
        $this->assertEquals('contado', $multaCobrada->forma_pago);
        $this->assertFloatEquals(5000.00, $multaCobrada->monto);
        $this->assertFloatEquals(5000.00, $asiento->monto);
        $this->assertEquals(1, $multaCobrada->items()->count());
    }

    public function test_flujo_multa_cobro_credito(): void
    {
        // 1. Crear multa
        $multa = Multa::factory()->enPesos()->create([
            'importe_original' => 3000.00,
        ]);

        // 2. Cobrar a crédito
        $multaCobrada = TesMultasCobradas::factory()->credito()->create([
            'fecha' => '2026-08-14',
            'monto' => 3000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa->codigo,
            'subtotal' => 3000.00,
        ]);

        // 3. Registrar asiento (entrada pendiente de cobro)
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas a Crédito')->id,
            'medio_id' => $this->getMedioDePago('CR')->id,
            'monto' => 3000.00,
            'documento_referencia' => 'MULTA-CRED-' . $multaCobrada->id,
        ]);

        // Verificaciones
        $this->assertEquals('credito', $multaCobrada->forma_pago);
        $this->assertFloatEquals(3000.00, $asiento->monto);
    }

    public function test_flujo_multa_con_multiples_items(): void
    {
        // 1. Crear varias multas
        $multa1 = Multa::factory()->enPesos()->create(['importe_original' => 2000.00]);
        $multa2 = Multa::factory()->enPesos()->create(['importe_original' => 1500.00]);
        $multa3 = Multa::factory()->enPesos()->create(['importe_original' => 1000.00]);

        // 2. Cobrar en un solo recibo
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'monto' => 4500.00,
        ]);

        // 3. Agregar items
        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa1->codigo,
            'subtotal' => 2000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa2->codigo,
            'subtotal' => 1500.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa3->codigo,
            'subtotal' => 1000.00,
        ]);

        // 4. Registrar asiento total
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 4500.00,
            'documento_referencia' => 'MULTA-' . $multaCobrada->recibo,
        ]);

        // Verificaciones
        $this->assertEquals(3, $multaCobrada->items()->count());
        $this->assertFloatEquals(4500.00, $multaCobrada->items->sum('subtotal'));
        $this->assertFloatEquals(4500.00, $asiento->monto);
    }

    public function test_flujo_multa_con_multiples_medios_pago(): void
    {
        // 1. Crear multa
        $multa = Multa::factory()->enPesos()->create([
            'importe_original' => 10000.00,
        ]);

        // 2. Cobrar con múltiples medios
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'monto' => 10000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa->codigo,
            'subtotal' => 10000.00,
        ]);

        // 3. Registrar asientos por cada medio de pago
        $medioEfectivo = $this->getMedioDePago('EF');
        $medioTarjeta = $this->getMedioDePago('TD');

        $asiento1 = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 6000.00,
            'documento_referencia' => 'MULTA-' . $multaCobrada->recibo . '-EF',
        ]);

        $asiento2 = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $medioTarjeta->id,
            'monto' => 4000.00,
            'documento_referencia' => 'MULTA-' . $multaCobrada->recibo . '-TD',
        ]);

        // Verificaciones
        $totalAsientos = $asiento1->monto + $asiento2->monto;
        $this->assertFloatEquals(10000.00, $totalAsientos);
    }

    public function test_flujo_dia_completo_multiples_multas(): void
    {
        $fecha = '2026-08-14';
        $medioEfectivo = $this->getMedioDePago('EF');
        $concepto = $this->getConcepto(LbConcepto::RECAUDACION_222);
        $detalle = $this->getDetalle('Multas');

        // Simular un día de trabajo: cobro de múltiples multas

        // 1. Multa 1: Contado
        $multa1 = TesMultasCobradas::factory()->contado()->create([
            'fecha' => $fecha,
            'monto' => 2000.00,
        ]);

        $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $concepto->id,
            'detalle_id' => $detalle->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 2000.00,
            'documento_referencia' => 'MULTA-' . $multa1->recibo,
        ]);

        // 2. Multa 2: Contado
        $multa2 = TesMultasCobradas::factory()->contado()->create([
            'fecha' => $fecha,
            'monto' => 3500.00,
        ]);

        $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $concepto->id,
            'detalle_id' => $detalle->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 3500.00,
            'documento_referencia' => 'MULTA-' . $multa2->recibo,
        ]);

        // 3. Multa 3: Contado
        $multa3 = TesMultasCobradas::factory()->contado()->create([
            'fecha' => $fecha,
            'monto' => 1500.00,
        ]);

        $this->libroService->registrarAsiento([
            'fecha' => $fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $concepto->id,
            'detalle_id' => $detalle->id,
            'medio_id' => $medioEfectivo->id,
            'monto' => 1500.00,
            'documento_referencia' => 'MULTA-' . $multa3->recibo,
        ]);

        // 4. Verificar totales del día
        $totalMultasCobradas = TesMultasCobradas::whereDate('fecha', $fecha)->sum('monto');
        $totalAsientos = LibroDiario::whereDate('fecha', $fecha)
            ->where('documento_referencia', 'LIKE', 'MULTA-%')
            ->sum('monto');

        $this->assertFloatEquals(7000.00, $totalMultasCobradas);
        $this->assertFloatEquals(7000.00, $totalAsientos);

        // 5. Verificar saldo acumulado
        $saldoFinal = $this->getSaldoSubcuenta($detalle->id);
        $this->assertGreaterThanOrEqual(7000.00, $saldoFinal);
    }

    public function test_flujo_multa_articulo_184(): void
    {
        // Multas Artículo 184: caso especial

        // 1. Crear multa Art. 184
        $multa = Multa::factory()->articulo184()->create([
            'importe_original' => 8000.00,
        ]);

        // 2. Cobrar
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'monto' => 8000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa->codigo,
            'descripcion' => $multa->descripcion,
            'subtotal' => 8000.00,
        ]);

        // 3. Registrar asiento
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 8000.00,
            'documento_referencia' => 'MULTA-ART184-' . $multaCobrada->recibo,
        ]);

        // Verificaciones
        $this->assertEquals('184', $multa->articulo);
        $this->assertFloatEquals(8000.00, $asiento->monto);
    }

    public function test_flujo_multa_en_ur_convertida_pesos(): void
    {
        // 1. Crear multa en UR
        $multa = Multa::factory()->enUR()->create([
            'monto_ur' => 50.00,
            'importe_original' => 5000.00, // 50 UR * 100 $/UR (ejemplo)
        ]);

        // 2. Cobrar en pesos
        $multaCobrada = TesMultasCobradas::factory()->contado()->create([
            'fecha' => '2026-08-14',
            'monto' => 5000.00,
        ]);

        TesMultasItems::factory()->create([
            'tes_multas_cobradas_id' => $multaCobrada->id,
            'codigo' => $multa->codigo,
            'monto_ur' => 50.00,
            'subtotal' => 5000.00,
        ]);

        // 3. Registrar asiento en pesos
        $asiento = $this->libroService->registrarAsiento([
            'fecha' => $multaCobrada->fecha,
            'tipo_id' => $this->getTipo('Entrada')->id,
            'signo_efectivo' => 1,
            'concepto_id' => $this->getConcepto(LbConcepto::RECAUDACION_222)->id,
            'detalle_id' => $this->getDetalle('Multas')->id,
            'medio_id' => $this->getMedioDePago('EF')->id,
            'monto' => 5000.00,
            'documento_referencia' => 'MULTA-UR-' . $multaCobrada->recibo,
        ]);

        // Verificaciones
        $this->assertEquals('UR', $multa->moneda);
        $this->assertFloatEquals(50.00, $multa->monto_ur);
        $this->assertFloatEquals(5000.00, $asiento->monto);
    }
}
