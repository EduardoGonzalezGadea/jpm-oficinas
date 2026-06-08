<?php

namespace Tests\Unit\Services;

use App\Services\ValorUrService;
use Carbon\Carbon;
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
}
