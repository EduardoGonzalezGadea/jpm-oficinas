<?php

namespace Tests\Feature\Tesoreria\Multas;

use App\Models\Tesoreria\Multa;
use App\Models\Tesoreria\TesMultasCobradas;
use Tests\TesoreriaTestCase;

/**
 * Tests de Búsqueda y Filtrado de Multas
 * 
 * Cubre:
 * - Búsqueda por artículo
 * - Búsqueda por descripción
 * - Filtrado por moneda
 * - Filtrado por visibilidad
 * - Búsquedas combinadas
 * - Ordenamiento
 */
class MultasSearchTest extends TesoreriaTestCase
{
    public function test_buscar_multas_por_articulo_exacto(): void
    {
        Multa::factory()->count(3)->create(['articulo' => '103']);
        Multa::factory()->count(2)->create(['articulo' => '184']);
        Multa::factory()->create(['articulo' => '200']);

        $resultados = Multa::where('articulo', '103')->get();

        $this->assertCount(3, $resultados);
        foreach ($resultados as $multa) {
            $this->assertEquals('103', $multa->articulo);
        }
    }

    public function test_buscar_multas_por_articulo_completo(): void
    {
        Multa::factory()->create([
            'articulo' => '103',
            'apartado' => '2A',
        ]);

        Multa::factory()->create([
            'articulo' => '103',
            'apartado' => '5B',
        ]);

        Multa::factory()->create([
            'articulo' => '103',
            'apartado' => null,
        ]);

        $resultados = Multa::where('articulo_completo', '103.2A')->get();

        $this->assertCount(1, $resultados);
        $this->assertEquals('103.2A', $resultados->first()->articulo_completo);
    }

    public function test_buscar_por_descripcion_parcial(): void
    {
        Multa::factory()->create(['descripcion' => 'Exceso de velocidad en autopista']);
        Multa::factory()->create(['descripcion' => 'Exceso de velocidad en zona escolar']);
        Multa::factory()->create(['descripcion' => 'Estacionamiento prohibido']);

        $resultados = Multa::buscarDescripcion('velocidad')->get();

        $this->assertCount(2, $resultados);
        foreach ($resultados as $multa) {
            $this->assertStringContainsString('velocidad', strtolower($multa->descripcion));
        }
    }

    public function test_buscar_por_descripcion_case_insensitive(): void
    {
        Multa::factory()->create(['descripcion' => 'EXCESO DE VELOCIDAD']);
        Multa::factory()->create(['descripcion' => 'exceso de velocidad']);
        Multa::factory()->create(['descripcion' => 'Exceso De Velocidad']);

        $resultados = Multa::buscarDescripcion('velocidad')->get();

        $this->assertCount(3, $resultados);
    }

    public function test_filtrar_multas_por_moneda(): void
    {
        Multa::factory()->count(3)->enPesos()->create();
        Multa::factory()->count(2)->enUR()->create();
        Multa::factory()->count(1)->enUI()->create();

        $enPesos = Multa::where('moneda', 'UYU')->get();
        $enUR = Multa::where('moneda', 'UR')->get();
        $enUI = Multa::where('moneda', 'UI')->get();

        $this->assertCount(3, $enPesos);
        $this->assertCount(2, $enUR);
        $this->assertCount(1, $enUI);
    }

    public function test_filtrar_multas_visibles(): void
    {
        Multa::factory()->count(5)->create(['visible' => true]);
        Multa::factory()->count(2)->create(['visible' => false]);

        $visibles = Multa::where('visible', true)->get();

        $this->assertCount(5, $visibles);
    }

    public function test_filtrar_multas_no_visibles(): void
    {
        Multa::factory()->count(5)->create(['visible' => true]);
        Multa::factory()->count(2)->create(['visible' => false]);

        $noVisibles = Multa::where('visible', false)->get();

        $this->assertCount(2, $noVisibles);
    }

    public function test_buscar_por_codigo(): void
    {
        Multa::factory()->create(['codigo' => 'M001']);
        Multa::factory()->create(['codigo' => 'M002']);
        Multa::factory()->create(['codigo' => 'M003']);

        $multa = Multa::where('codigo', 'M002')->first();

        $this->assertNotNull($multa);
        $this->assertEquals('M002', $multa->codigo);
    }

    public function test_buscar_por_literal(): void
    {
        Multa::factory()->count(3)->create(['literal' => 'A']);
        Multa::factory()->count(2)->create(['literal' => 'B']);

        $literalA = Multa::where('literal', 'A')->get();

        $this->assertCount(3, $literalA);
    }

    public function test_buscar_articulo_con_scope(): void
    {
        Multa::factory()->count(4)->create(['articulo' => '184']);
        Multa::factory()->count(2)->create(['articulo' => '200']);

        $articulo184 = Multa::porArticulo('184')->get();

        $this->assertCount(4, $articulo184);
    }

    public function test_buscar_combinando_articulo_y_descripcion(): void
    {
        Multa::factory()->create([
            'articulo' => '103',
            'descripcion' => 'Exceso de velocidad',
        ]);

        Multa::factory()->create([
            'articulo' => '103',
            'descripcion' => 'Estacionamiento prohibido',
        ]);

        Multa::factory()->create([
            'articulo' => '184',
            'descripcion' => 'Exceso de velocidad',
        ]);

        $resultados = Multa::porArticulo('103')
            ->buscarDescripcion('velocidad')
            ->get();

        $this->assertCount(1, $resultados);
        $this->assertEquals('103', $resultados->first()->articulo);
        $this->assertStringContainsString('velocidad', strtolower($resultados->first()->descripcion));
    }

    public function test_filtrar_por_rango_de_importes(): void
    {
        Multa::factory()->create(['importe_original' => 1000.00]);
        Multa::factory()->create(['importe_original' => 2500.00]);
        Multa::factory()->create(['importe_original' => 5000.00]);
        Multa::factory()->create(['importe_original' => 7500.00]);

        $resultados = Multa::whereBetween('importe_original', [2000, 6000])->get();

        $this->assertCount(2, $resultados);
    }

    public function test_buscar_por_decreto(): void
    {
        Multa::factory()->count(3)->create(['decreto' => 'Decreto 123/2025']);
        Multa::factory()->count(2)->create(['decreto' => 'Decreto 456/2025']);

        $resultados = Multa::where('decreto', 'Decreto 123/2025')->get();

        $this->assertCount(3, $resultados);
    }

    public function test_ordenar_por_articulo(): void
    {
        Multa::factory()->create(['articulo' => '200']);
        Multa::factory()->create(['articulo' => '103']);
        Multa::factory()->create(['articulo' => '184']);

        $resultados = Multa::orderBy('articulo', 'asc')->get();

        $this->assertEquals('103', $resultados->first()->articulo);
        $this->assertEquals('200', $resultados->last()->articulo);
    }

    public function test_ordenar_por_importe(): void
    {
        Multa::factory()->create(['importe_original' => 5000.00]);
        Multa::factory()->create(['importe_original' => 1000.00]);
        Multa::factory()->create(['importe_original' => 3000.00]);

        $resultados = Multa::orderBy('importe_original', 'desc')->get();

        $this->assertFloatEquals(5000.00, $resultados->first()->importe_original);
        $this->assertFloatEquals(1000.00, $resultados->last()->importe_original);
    }

    public function test_buscar_multas_sin_eliminar(): void
    {
        Multa::factory()->count(3)->create();
        $eliminada = Multa::factory()->create();
        $eliminada->delete();

        $activas = Multa::all();

        $this->assertCount(3, $activas);
    }

    public function test_buscar_multas_incluyendo_eliminadas(): void
    {
        Multa::factory()->count(3)->create();
        $eliminada = Multa::factory()->create();
        $eliminada->delete();

        $todas = Multa::withTrashed()->get();

        $this->assertCount(4, $todas);
    }

    public function test_buscar_solo_multas_eliminadas(): void
    {
        Multa::factory()->count(3)->create();
        $eliminada = Multa::factory()->create();
        $eliminada->delete();

        $eliminadas = Multa::onlyTrashed()->get();

        $this->assertCount(1, $eliminadas);
    }

    public function test_buscar_recibos_por_cedula(): void
    {
        TesMultasCobradas::factory()->count(3)->create(['cedula' => '12345678']);
        TesMultasCobradas::factory()->count(2)->create(['cedula' => '87654321']);

        $resultados = TesMultasCobradas::where('cedula', '12345678')->get();

        $this->assertCount(3, $resultados);
    }

    public function test_buscar_recibos_por_nombre(): void
    {
        TesMultasCobradas::factory()->create(['nombre' => 'Juan Pérez']);
        TesMultasCobradas::factory()->create(['nombre' => 'María Pérez']);
        TesMultasCobradas::factory()->create(['nombre' => 'Pedro González']);

        $resultados = TesMultasCobradas::where('nombre', 'LIKE', '%Pérez%')->get();

        $this->assertCount(2, $resultados);
    }

    public function test_buscar_recibos_por_rango_fechas(): void
    {
        TesMultasCobradas::factory()->create(['fecha' => '2026-08-01']);
        TesMultasCobradas::factory()->create(['fecha' => '2026-08-10']);
        TesMultasCobradas::factory()->create(['fecha' => '2026-08-20']);

        $resultados = TesMultasCobradas::whereBetween('fecha', ['2026-08-05', '2026-08-15'])->get();

        $this->assertCount(1, $resultados);
    }

    public function test_buscar_recibos_por_forma_pago(): void
    {
        TesMultasCobradas::factory()->count(4)->contado()->create();
        TesMultasCobradas::factory()->count(2)->credito()->create();

        $contado = TesMultasCobradas::where('forma_pago', 'contado')->get();
        $credito = TesMultasCobradas::where('forma_pago', 'credito')->get();

        $this->assertCount(4, $contado);
        $this->assertCount(2, $credito);
    }

    public function test_buscar_recibos_por_monto(): void
    {
        TesMultasCobradas::factory()->create(['monto' => 1000.00]);
        TesMultasCobradas::factory()->create(['monto' => 5000.00]);
        TesMultasCobradas::factory()->create(['monto' => 10000.00]);

        $mayores = TesMultasCobradas::where('monto', '>=', 5000)->get();

        $this->assertCount(2, $mayores);
    }
}
