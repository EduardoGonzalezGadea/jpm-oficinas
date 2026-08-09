<?php

namespace Tests\Unit\Models\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LbConceptoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_obtener_concepto_caja_chica_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);

        // Act
        $resultado = LbConcepto::cajaChica();

        // Assert
        $this->assertInstanceOf(LbConcepto::class, $resultado);
        $this->assertEquals($concepto->id, $resultado->id);
        $this->assertEquals(LbConcepto::CAJA_CHICA, $resultado->nombre);
    }

    /** @test */
    public function lanza_excepcion_cuando_concepto_caja_chica_no_existe()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');
        $this->expectExceptionMessage(LbConcepto::CAJA_CHICA);

        // Act
        LbConcepto::cajaChica();
    }

    /** @test */
    public function puede_obtener_concepto_recaudacion_222_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_222]);

        // Act
        $resultado = LbConcepto::recaudacion222();

        // Assert
        $this->assertInstanceOf(LbConcepto::class, $resultado);
        $this->assertEquals($concepto->id, $resultado->id);
        $this->assertEquals(LbConcepto::RECAUDACION_222, $resultado->nombre);
    }

    /** @test */
    public function lanza_excepcion_cuando_concepto_recaudacion_222_no_existe()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');
        $this->expectExceptionMessage(LbConcepto::RECAUDACION_222);

        // Act
        LbConcepto::recaudacion222();
    }

    /** @test */
    public function puede_obtener_concepto_recaudacion_diaria_cuando_existe()
    {
        // Arrange
        $concepto = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_DIARIA]);

        // Act
        $resultado = LbConcepto::recaudacionDiaria();

        // Assert
        $this->assertInstanceOf(LbConcepto::class, $resultado);
        $this->assertEquals($concepto->id, $resultado->id);
        $this->assertEquals(LbConcepto::RECAUDACION_DIARIA, $resultado->nombre);
    }

    /** @test */
    public function lanza_excepcion_cuando_concepto_recaudacion_diaria_no_existe()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');
        $this->expectExceptionMessage(LbConcepto::RECAUDACION_DIARIA);

        // Act
        LbConcepto::recaudacionDiaria();
    }
}
