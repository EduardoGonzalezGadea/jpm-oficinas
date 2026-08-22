<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Services\Tesoreria\CfeUniversalParserService;
use Tests\TestCase;

class CfeParserSeguridadTest extends TestCase
{
    protected bool $requiresDatabase = false;

    private CfeUniversalParserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CfeUniversalParserService();
    }

    public function test_sanitiza_caracteres_de_control(): void
    {
        $textoConControles = "Línea válida\x00\x01\x02\x03Otra línea";
        $resultado = $this->invocarSanitizar($textoConControles);

        $this->assertStringNotContainsString("\x00", $resultado);
        $this->assertStringNotContainsString("\x01", $resultado);
        $this->assertStringContainsString('Línea válida', $resultado);
        $this->assertStringContainsString('Otra línea', $resultado);
    }

    public function test_sanitiza_secuencias_ansi_escape(): void
    {
        $textoConEscape = "Texto\x1B[31mcon color\x1B[0m normal";
        $resultado = $this->invocarSanitizar($textoConEscape);

        $this->assertStringNotContainsString("\x1B", $resultado);
        $this->assertStringContainsString('Texto', $resultado);
        $this->assertStringContainsString('normal', $resultado);
    }

    public function test_normaliza_saltos_de_linea(): void
    {
        $textoConCr = "Línea1\r\nLínea2\rLínea3";
        $resultado = $this->invocarSanitizar($textoConCr);

        $this->assertStringNotContainsString("\r", $resultado);
        $this->assertStringContainsString("Línea1\nLínea2\nLínea3", $resultado);
    }

    public function test_elimina_espacios_multiples(): void
    {
        $textoConEspacios = "texto   con    muchos     espacios";
        $resultado = $this->invocarSanitizar($textoConEspacios);

        $this->assertStringNotContainsString('   ', $resultado);
        $this->assertEquals('texto con muchos espacios', $resultado);
    }

    public function test_datos_vacios_retorna_estructura_valida(): void
    {
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('datosVacios');
        $method->setAccessible(true);
        $datos = $method->invoke($this->service);

        $this->assertIsArray($datos);
        $this->assertArrayHasKey('documento_tipo', $datos);
        $this->assertArrayHasKey('emisor_ruc', $datos);
        $this->assertArrayHasKey('total_a_pagar', $datos);
        $this->assertArrayHasKey('items', $datos);
        $this->assertEmpty($datos['documento_tipo']);
        $this->assertEquals(0.0, $datos['total_a_pagar']);
    }

    public function test_validar_datos_detecta_campos_vacios(): void
    {
        $datosVacios = [
            'documento_tipo' => '',
            'documento_numero' => '',
            'emisor_ruc' => '',
            'total_a_pagar' => 0,
            'monto_total' => 0,
            'fecha' => null,
            'documento_serie' => '',
        ];

        $errores = $this->service->validarDatos($datosVacios);

        $this->assertNotEmpty($errores);
        $this->assertContains('No se pudo detectar el tipo de documento CFE', $errores);
        $this->assertContains('No se pudo extraer el número de documento', $errores);
        $this->assertContains('RUC del emisor inválido o no detectado', $errores);
        $this->assertContains('No se pudo extraer el monto total a pagar', $errores);
    }

    public function test_validar_datos_ruc_invalido(): void
    {
        $datos = $this->datosValidosBase();
        $datos['emisor_ruc'] = '123'; // Muy corto

        $errores = $this->service->validarDatos($datos);

        $this->assertContains('RUC del emisor inválido o no detectado', $errores);
    }

    public function test_validar_datos_fecha_invalida(): void
    {
        $datos = $this->datosValidosBase();
        $datos['fecha'] = 'fecha-malo';

        $errores = $this->service->validarDatos($datos);

        $this->assertContains('Fecha extraída inválida: fecha-malo', $errores);
    }

    public function test_validar_datos_serie_invalida(): void
    {
        $datos = $this->datosValidosBase();
        $datos['documento_serie'] = '12'; // Debe ser una sola letra

        $errores = $this->service->validarDatos($datos);

        $this->assertContains('Serie del documento inválida: 12', $errores);
    }

    public function test_validar_datos_numero_invalido(): void
    {
        $datos = $this->datosValidosBase();
        $datos['documento_numero'] = 'ABC123'; // Debe ser solo dígitos

        $errores = $this->service->validarDatos($datos);

        $this->assertContains('Número del documento inválido: ABC123', $errores);
    }

    public function test_validar_datos_validos_no_tiene_errores(): void
    {
        $datos = $this->datosValidosBase();

        $errores = $this->service->validarDatos($datos);

        $this->assertEmpty($errores, 'Errores inesperados: ' . implode(', ', $errores));
    }

    private function datosValidosBase(): array
    {
        return [
            'documento_tipo' => 'e-Factura',
            'documento_numero' => '12345',
            'emisor_ruc' => '214567890123',
            'total_a_pagar' => 1500.0,
            'monto_total' => 1500.0,
            'fecha' => '21/08/2026',
            'documento_serie' => 'A',
        ];
    }

    private function invocarSanitizar(string $texto): string
    {
        $ref = new \ReflectionClass($this->service);
        $method = $ref->getMethod('sanitizarTexto');
        $method->setAccessible(true);

        return $method->invoke($this->service, $texto);
    }
}
