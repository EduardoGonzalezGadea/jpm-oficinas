<?php

namespace Tests\Unit\Models\Tesoreria;

use App\Models\Tesoreria\MedioDePago;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedioDePagoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function puede_obtener_medio_efectivo_cuando_existe()
    {
        // Arrange
        $medio = MedioDePago::create([
            'nombre' => MedioDePago::EFECTIVO,
            'activo' => true,
        ]);

        // Act
        $resultado = MedioDePago::efectivo();

        // Assert
        $this->assertInstanceOf(MedioDePago::class, $resultado);
        $this->assertEquals($medio->id, $resultado->id);
        $this->assertEquals(MedioDePago::EFECTIVO, $resultado->nombre);
        $this->assertTrue($resultado->activo);
    }

    /** @test */
    public function lanza_excepcion_cuando_medio_efectivo_no_existe()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');
        $this->expectExceptionMessage(MedioDePago::EFECTIVO);

        // Act
        MedioDePago::efectivo();
    }

    /** @test */
    public function lanza_excepcion_cuando_medio_efectivo_existe_pero_esta_inactivo()
    {
        // Arrange
        MedioDePago::create([
            'nombre' => MedioDePago::EFECTIVO,
            'activo' => false,
        ]);

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');

        // Act
        MedioDePago::efectivo();
    }

    /** @test */
    public function puede_obtener_medio_transferencia_cuando_existe()
    {
        // Arrange
        $medio = MedioDePago::create([
            'nombre' => 'Transferencia Bancaria',
            'activo' => true,
        ]);

        // Act
        $resultado = MedioDePago::transferencia();

        // Assert
        $this->assertInstanceOf(MedioDePago::class, $resultado);
        $this->assertEquals($medio->id, $resultado->id);
        $this->assertStringContainsString('Transferencia', $resultado->nombre);
        $this->assertTrue($resultado->activo);
    }

    /** @test */
    public function puede_obtener_medio_transferencia_con_busqueda_flexible()
    {
        // Arrange - distintas variaciones del nombre
        $medio = MedioDePago::create([
            'nombre' => 'Transferencia Electrónica',
            'activo' => true,
        ]);

        // Act
        $resultado = MedioDePago::transferencia();

        // Assert
        $this->assertInstanceOf(MedioDePago::class, $resultado);
        $this->assertEquals($medio->id, $resultado->id);
    }

    /** @test */
    public function lanza_excepcion_cuando_medio_transferencia_no_existe()
    {
        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');
        $this->expectExceptionMessage(MedioDePago::TRANSFERENCIA);

        // Act
        MedioDePago::transferencia();
    }

    /** @test */
    public function lanza_excepcion_cuando_medio_transferencia_existe_pero_esta_inactivo()
    {
        // Arrange
        MedioDePago::create([
            'nombre' => 'Transferencia Bancaria',
            'activo' => false,
        ]);

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ERROR DE CONFIGURACIÓN');

        // Act
        MedioDePago::transferencia();
    }
}
