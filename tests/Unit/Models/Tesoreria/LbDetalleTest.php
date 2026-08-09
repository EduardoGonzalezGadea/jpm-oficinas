<?php

namespace Tests\Unit\Models\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LbDetalleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_obtener_detalle_fondo_fijo_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);
        $detalle = LbDetalle::create([
            'concepto_id' => $concepto->id,
            'nombre' => LbDetalle::FONDO_FIJO,
        ]);

        // Act
        $resultado = LbDetalle::fondoFijo();

        // Assert
        $this->assertInstanceOf(LbDetalle::class, $resultado);
        $this->assertEquals($detalle->id, $resultado->id);
        $this->assertEquals(LbDetalle::FONDO_FIJO, $resultado->nombre);
    }

    /** @test */
    public function lanza_excepcion_cuando_concepto_caja_chica_no_existe_al_buscar_fondo_fijo()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(LbConcepto::CAJA_CHICA);

        // Act
        LbDetalle::fondoFijo();
    }

    /** @test */
    public function lanza_excepcion_cuando_detalle_fondo_fijo_no_existe()
    {
        // Arrange
        LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(LbDetalle::FONDO_FIJO);

        // Act
        LbDetalle::fondoFijo();
    }

    /** @test */
    public function puede_obtener_detalle_pendiente_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);
        $detalle = LbDetalle::create([
            'concepto_id' => $concepto->id,
            'nombre' => LbDetalle::PENDIENTE,
        ]);

        // Act
        $resultado = LbDetalle::pendiente();

        // Assert
        $this->assertInstanceOf(LbDetalle::class, $resultado);
        $this->assertEquals($detalle->id, $resultado->id);
        $this->assertEquals(LbDetalle::PENDIENTE, $resultado->nombre);
    }

    /** @test */
    public function puede_obtener_detalle_pagos_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);
        $detalle = LbDetalle::create([
            'concepto_id' => $concepto->id,
            'nombre' => LbDetalle::PAGOS,
        ]);

        // Act
        $resultado = LbDetalle::pagos();

        // Assert
        $this->assertInstanceOf(LbDetalle::class, $resultado);
        $this->assertEquals($detalle->id, $resultado->id);
        $this->assertEquals(LbDetalle::PAGOS, $resultado->nombre);
    }

    /** @test */
    public function puede_obtener_detalle_recaudaciones_varias_222_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_222]);
        $detalle = LbDetalle::create([
            'concepto_id' => $concepto->id,
            'nombre' => LbDetalle::RECAUDACIONES_VARIAS_222,
        ]);

        // Act
        $resultado = LbDetalle::recaudacionesVarias222();

        // Assert
        $this->assertInstanceOf(LbDetalle::class, $resultado);
        $this->assertEquals($detalle->id, $resultado->id);
        $this->assertEquals(LbDetalle::RECAUDACIONES_VARIAS_222, $resultado->nombre);
    }

    /** @test */
    public function puede_obtener_detalle_otras_recaudaciones_varias_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_DIARIA]);
        $detalle = LbDetalle::create([
            'concepto_id' => $concepto->id,
            'nombre' => LbDetalle::OTRAS_RECAUDACIONES_VARIAS,
        ]);

        // Act
        $resultado = LbDetalle::otrasRecaudacionesVarias();

        // Assert
        $this->assertInstanceOf(LbDetalle::class, $resultado);
        $this->assertEquals($detalle->id, $resultado->id);
        $this->assertEquals(LbDetalle::OTRAS_RECAUDACIONES_VARIAS, $resultado->nombre);
    }
}
