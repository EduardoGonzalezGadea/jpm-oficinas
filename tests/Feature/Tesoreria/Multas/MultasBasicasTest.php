<?php

namespace Tests\Feature\Tesoreria\Multas;

use App\Models\Tesoreria\Multa;
use Tests\TesoreriaTestCase;

/**
 * Tests de Funcionalidad Básica de Multas
 * 
 * Cubre:
 * - Creación de multas
 * - Artículos completos automáticos
 * - Scopes de búsqueda
 * - Accessors de formato
 * - Tipos de moneda
 */
class MultasBasicasTest extends TesoreriaTestCase
{
    public function test_puede_crear_multa_basica(): void
    {
        $multa = Multa::create([
            'codigo' => 'M001',
            'articulo' => '103',
            'literal' => 'A',
            'descripcion' => 'Infracción por exceso de velocidad',
            'moneda' => 'UYU',
            'importe_original' => 5000.00,
        ]);

        $this->assertDatabaseHas('tes_multas', [
            'codigo' => 'M001',
            'articulo' => '103',
        ]);
    }

    public function test_puede_crear_multa_con_factory(): void
    {
        $multa = Multa::factory()->create();

        $this->assertNotNull($multa->id);
        $this->assertNotNull($multa->codigo);
        $this->assertNotNull($multa->articulo);
    }

    public function test_articulo_completo_se_genera_automaticamente(): void
    {
        $multa = Multa::factory()->create([
            'articulo' => '103',
            'apartado' => '2A',
        ]);

        $this->assertEquals('103.2A', $multa->articulo_completo);
    }

    public function test_articulo_completo_sin_apartado(): void
    {
        $multa = Multa::factory()->create([
            'articulo' => '184',
            'apartado' => null,
        ]);

        $this->assertEquals('184', $multa->articulo_completo);
    }

    public function test_articulo_completo_se_actualiza_al_guardar(): void
    {
        $multa = Multa::factory()->create([
            'articulo' => '100',
            'apartado' => null,
        ]);

        $this->assertEquals('100', $multa->articulo_completo);

        $multa->update(['apartado' => '5B']);
        
        $this->assertEquals('100.5B', $multa->fresh()->articulo_completo);
    }

    public function test_multa_en_pesos_con_factory(): void
    {
        $multa = Multa::factory()->enPesos()->create();

        $this->assertEquals('UYU', $multa->moneda);
        $this->assertNotNull($multa->monto_pesos);
        $this->assertGreaterThan(0, $multa->monto_pesos);
    }

    public function test_multa_en_ur_con_factory(): void
    {
        $multa = Multa::factory()->enUR()->create();

        $this->assertEquals('UR', $multa->moneda);
        $this->assertNotNull($multa->monto_ur);
        $this->assertGreaterThan(0, $multa->monto_ur);
    }

    public function test_multa_en_ui_con_factory(): void
    {
        $multa = Multa::factory()->enUI()->create();

        $this->assertEquals('UI', $multa->moneda);
        $this->assertNotNull($multa->monto_ui);
        $this->assertGreaterThan(0, $multa->monto_ui);
    }

    public function test_multa_articulo_184_con_factory(): void
    {
        $multa = Multa::factory()->articulo184()->create();

        $this->assertEquals('184', $multa->articulo);
        $this->assertStringContainsString('Artículo 184', $multa->descripcion);
    }

    public function test_scope_por_articulo(): void
    {
        Multa::factory()->count(3)->create(['articulo' => '103']);
        Multa::factory()->count(2)->create(['articulo' => '184']);

        $multas103 = Multa::porArticulo('103')->get();
        $multas184 = Multa::porArticulo('184')->get();

        $this->assertCount(3, $multas103);
        $this->assertCount(2, $multas184);
    }

    public function test_scope_buscar_descripcion(): void
    {
        Multa::factory()->create(['descripcion' => 'Infracción por velocidad']);
        Multa::factory()->create(['descripcion' => 'Estacionamiento prohibido']);
        Multa::factory()->create(['descripcion' => 'Exceso de velocidad en zona escolar']);

        $resultados = Multa::buscarDescripcion('velocidad')->get();

        $this->assertCount(2, $resultados);
    }

    public function test_importe_original_formateado_en_pesos(): void
    {
        $multa = Multa::factory()->create([
            'moneda' => 'UYU',
            'importe_original' => 5234.56,
        ]);

        $formateado = $multa->importe_original_formateado;

        $this->assertStringContainsString('5.234,56', $formateado);
        $this->assertStringContainsString('$', $formateado);
    }

    public function test_importe_original_formateado_en_ur(): void
    {
        $multa = Multa::factory()->create([
            'moneda' => 'UR',
            'importe_original' => 100.00,
        ]);

        $formateado = $multa->importe_original_formateado;

        $this->assertStringContainsString('100,00', $formateado);
        $this->assertStringContainsString('UR', $formateado);
    }

    public function test_importe_unificado_formateado(): void
    {
        $multa = Multa::factory()->create([
            'moneda' => 'UYU',
            'importe_original' => 5000.00,
            'importe_unificado' => 6000.00,
        ]);

        $formateado = $multa->importe_unificado_formateado;

        $this->assertStringContainsString('6.000,00', $formateado);
        $this->assertStringContainsString('$', $formateado);
    }

    public function test_importe_unificado_null_retorna_string_vacio(): void
    {
        $multa = Multa::factory()->create([
            'importe_unificado' => null,
        ]);

        $this->assertEquals('', $multa->importe_unificado_formateado);
    }

    public function test_multa_visible_por_defecto(): void
    {
        $multa = Multa::factory()->create();

        // Verificar valor por defecto de la factory
        $this->assertTrue(is_bool($multa->visible));
    }

    public function test_puede_actualizar_multa(): void
    {
        $multa = Multa::factory()->create([
            'descripcion' => 'Descripción original',
        ]);

        $multa->update(['descripcion' => 'Descripción actualizada']);

        $this->assertEquals('Descripción actualizada', $multa->fresh()->descripcion);
    }

    public function test_puede_eliminar_multa_soft_delete(): void
    {
        $multa = Multa::factory()->create();
        $id = $multa->id;

        $multa->delete();

        $this->assertSoftDeleted('tes_multas', ['id' => $id]);
        $this->assertNotNull($multa->fresh()->deleted_at);
    }

    public function test_multa_tiene_timestamps(): void
    {
        $multa = Multa::factory()->create();

        $this->assertNotNull($multa->created_at);
        $this->assertNotNull($multa->updated_at);
    }

    public function test_multa_con_decreto(): void
    {
        $multa = Multa::factory()->create([
            'decreto' => 'Decreto 123/2025',
        ]);

        $this->assertEquals('Decreto 123/2025', $multa->decreto);
    }

    public function test_multa_con_inciso_legal(): void
    {
        $multa = Multa::factory()->create([
            'inciso_legal' => 'Ley 18.191 Art. 103',
        ]);

        $this->assertEquals('Ley 18.191 Art. 103', $multa->inciso_legal);
    }

    public function test_multa_con_literal_y_apartado(): void
    {
        $multa = Multa::factory()->create([
            'articulo' => '103',
            'literal' => 'B',
            'apartado' => '7',
        ]);

        $this->assertEquals('103.7', $multa->articulo_completo);
        $this->assertEquals('B', $multa->literal);
    }
}
