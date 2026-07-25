<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Services\Tesoreria\CfeUniversalParserService;
use PHPUnit\Framework\TestCase;

class CfeUniversalParserServiceTest extends TestCase
{
    private CfeUniversalParserService $service;

    private string $bloqueBase = "214988770019 e-Ticket Cobranza\nA 2705 Contado\nNOMBRE O DENOMINACIÓN DOMICILIO FISCAL\nConsumidor Final\nFECHA\tMONEDA\n08/06/2026 Peso uruguayo\n";

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CfeUniversalParserService();
    }

    private function buildTexto(string $itemsBlock): string
    {
        return $this->bloqueBase
            . "DETALLE DESCRIPCIÓN CANT. PRECIO DESC. REC. IMPORTE\n"
            . $itemsBlock
            . "\nMONTO NO FACTURABLE:\t0,00\nMONTO TOTAL.:\t578,00\nTOTAL A PAGAR:\t578,00\nREFERENCIAS:\n";
    }

    // TRAMITE en línea propia antes de los números
    public function test_tramite_en_linea_propia(): void
    {
        $texto = $this->buildTexto(
            "Titulo Habilitación y Tenencia de\nArma (TAHTA)\nTRÁMITE 9763232\n1,000 (Unid) 578,00\t578,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertCount(1, $datos['items']);
        $this->assertSame('Titulo Habilitación y Tenencia de Arma (TAHTA)', $datos['items'][0]['detalle']);
        $this->assertSame('TRÁMITE 9763232', $datos['items'][0]['descripcion']);
        $this->assertSame(578.0, $datos['items'][0]['importe']);
    }

    // TRAMITE pegado a la línea de números
    public function test_tramite_pegado_a_numeros(): void
    {
        $texto = $this->buildTexto(
            "Titulo Habilitación y Tenencia de\nArma (TAHTA)\nTRÁMITE 9763232 1,000 (Unid) 578,00\t578,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Titulo Habilitación y Tenencia de Arma (TAHTA)', $datos['items'][0]['detalle']);
        $this->assertSame('TRÁMITE 9763232', $datos['items'][0]['descripcion']);
    }

    // Descripción partida + TRAMITE pegado a números (el caso que fallaba)
    public function test_descripcion_partida_con_tramite_pegado(): void
    {
        $texto = $this->buildTexto(
            "Titulo Habilitación y Tenencia\nde Arma (TAHTA) TRÁMITE 9886778 1,000 (Unid) 578,00\t578,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Titulo Habilitación y Tenencia de Arma (TAHTA)', $datos['items'][0]['detalle']);
        $this->assertSame('TRÁMITE 9886778', $datos['items'][0]['descripcion']);
    }

    // TRAMITE + ING en líneas separadas
    public function test_tramite_e_ing_en_lineas_separadas(): void
    {
        $texto = $this->buildTexto(
            "Titulo Habilitación y Tenencia de\nArma (TAHTA)\nTRAMITE N°: 99742\nING:36\n1,000 (Unid) 573,00\t573,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Titulo Habilitación y Tenencia de Arma (TAHTA)', $datos['items'][0]['detalle']);
        $this->assertStringContainsString('99742', $datos['items'][0]['descripcion']);
        $this->assertSame(573.0, $datos['items'][0]['importe']);
    }

    // Sin metadata: detalle completo, descripcion vacía
    public function test_sin_metadata(): void
    {
        $texto = $this->buildTexto(
            "Certificado de Residencia\n1,000 (Unid) 200,00\t200,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Certificado de Residencia', $datos['items'][0]['detalle']);
        $this->assertSame('', $datos['items'][0]['descripcion']);
        $this->assertSame(200.0, $datos['items'][0]['importe']);
    }

    // REIMPRESION no debe aparecer en detalle
    public function test_reimpresion_va_a_descripcion(): void
    {
        $texto = $this->buildTexto(
            "Titulo Habilitación y Tenencia de\nArma (TAHTA)\nREIMPRESION\nTRÁMITE 9763232\n1,000 (Unid) 578,00\t578,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Titulo Habilitación y Tenencia de Arma (TAHTA)', $datos['items'][0]['detalle']);
        $this->assertStringNotContainsStringIgnoringCase('REIMPRESION', $datos['items'][0]['detalle']);
    }

    // Arrendamientos: el parser combina todas las líneas en detalle (el corte por concepto lo hace el trait con BD)
    public function test_arrendamientos_con_descripcion_adicional(): void
    {
        $texto = $this->buildTexto(
            "Arrendamientos\nENERO 2026 AV ITALIA\nAPTO 602\n1,000 (Unid) 11.111,00\t11.111,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        // El parser no tiene BD: todo va a detalle, sin metadata que cortar
        $this->assertStringContainsStringIgnoringCase('Arrendamientos', $datos['items'][0]['detalle']);
        $this->assertStringContainsString('ENERO 2026', $datos['items'][0]['detalle']);
        $this->assertSame(11111.0, $datos['items'][0]['importe']);
    }

    // Arrendamientos sin descripcion adicional
    public function test_arrendamientos_solo(): void
    {
        $texto = $this->buildTexto(
            "Arrendamientos\t1,000 (Unid) 11.469,00\t11.469,00\n"
        );

        $datos = $this->service->extraerDatos($texto);

        $this->assertSame('Arrendamientos', $datos['items'][0]['detalle']);
        $this->assertSame('', $datos['items'][0]['descripcion']);
    }
}
