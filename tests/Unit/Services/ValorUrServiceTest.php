<?php

namespace Tests\Unit\Services;

use App\Services\ValorUrService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ValorUrServiceTest extends TestCase
{
    private ValorUrService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ValorUrService();
    }

    public function test_detecta_mes_vigente(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 4, 12, 0, 0, 'America/Montevideo'));

        $this->assertTrue($this->service->esMesVigente('Junio'));
        $this->assertFalse($this->service->esMesVigente('Mayo'));
        $this->assertFalse($this->service->esMesVigente('Noviembre'));
        $this->assertFalse($this->service->esMesVigente(null));
    }

    public function test_parsea_html_del_bps(): void
    {
        $html = <<<'HTML'
        <table>
            <tr>
                <td>Indicador</td>
                <td>Mayo</td>
                <td>Junio</td>
            </tr>
            <tr>
                <td>Unidad Reajustable (UR) (4)</td>
                <td>$ 1.917,35</td>
                <td>$ 1.921,36</td>
            </tr>
        </table>
        HTML;

        $resultado = $this->service->parseBpsHtml($html);

        $this->assertNotNull($resultado);
        $this->assertSame('$ 1.921,36', $resultado['valorUr']);
        $this->assertSame('Junio', $resultado['mesUr']);
    }

    public function test_marca_como_vencido_cuando_el_mes_no_es_actual(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 6, 4, 12, 0, 0, 'America/Montevideo'));

        $html = <<<'HTML'
        <table>
            <tr>
                <td>Indicador</td>
                <td>Mayo</td>
            </tr>
            <tr>
                <td>Unidad Reajustable (UR) (4)</td>
                <td>$ 1.917,35</td>
            </tr>
        </table>
        HTML;

        $parseado = $this->service->parseBpsHtml($html);

        $this->assertSame('Mayo', $parseado['mesUr']);
        $this->assertFalse($this->service->esMesVigente($parseado['mesUr']));
    }

    public function test_descarga_de_mes_no_vigente_renueva_ultimo_valor_conocido(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 12, 0, 0, 'America/Montevideo'));
        Cache::flush();

        $service = Mockery::mock(ValorUrService::class)->makePartial();
        $service->shouldReceive('fetchFromBps')->andReturn([
            'valorUr' => '$ 1.922,68',
            'mesUr' => 'Julio',
        ]);

        $resultado = $service->obtener();

        $this->assertSame('$ 1.922,68', $resultado['valorUr']);
        $this->assertSame('Julio', $resultado['mesUr']);
        $this->assertTrue($resultado['vencido']);
        $this->assertSame('bps', $resultado['fuente']);

        $ultimo = Cache::get('valor_ur_ultimo_valido');
        $this->assertSame('$ 1.922,68', $ultimo['valorUr']);

        // Si una descarga posterior falla, el fallback debe usar Julio (el último descargado)
        Cache::forget('valor_ur_completo');

        $service2 = Mockery::mock(ValorUrService::class)->makePartial();
        $service2->shouldReceive('fetchFromBps')->andReturn(null);

        $fallback = $service2->obtener();

        $this->assertSame('$ 1.922,68', $fallback['valorUr']);
        $this->assertSame('ultimo_valido', $fallback['fuente']);
    }

    public function test_devuelve_valor_cacheado_aunque_este_vencido(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 1, 12, 0, 0, 'America/Montevideo'));
        Cache::flush();

        Cache::put('valor_ur_completo', [
            'valorUr' => '$ 1.922,68',
            'mesUr' => 'Julio',
            'vencido' => true,
            'fuente' => 'bps',
        ], now()->addMinutes(240));

        $service = Mockery::mock(ValorUrService::class)->makePartial();
        $service->shouldReceive('fetchFromBps')->never();

        $resultado = $service->obtener();

        $this->assertSame('$ 1.922,68', $resultado['valorUr']);
        $this->assertTrue($resultado['vencido']);
    }
}
