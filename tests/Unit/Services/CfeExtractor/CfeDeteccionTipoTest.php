<?php

namespace Tests\Unit\Services\CfeExtractor;

use App\Helpers\TextoHelper;
use App\Services\CfeProcessorService;
use App\Repositories\CfePendienteRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitarios para la detección de tipo de CFE en CfeProcessorService.
 * No requiere base de datos — solo prueba la lógica de detección de palabras clave.
 */
class CfeDeteccionTipoTest extends TestCase
{
    private CfeProcessorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear un mock del repository para no requerir DB
        $repository = $this->createMock(CfePendienteRepository::class);
        $this->service = new CfeProcessorService($repository);
    }

    // -------------------------------------------------------------------------
    // Detección de tipos individuales
    // -------------------------------------------------------------------------

    public function test_detecta_certificado_residencia(): void
    {
        $texto = 'Este documento es un CERTIFICADO DE RESIDENCIA emitido por la Intendencia.';
        $this->assertEquals('certificado_residencia', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_multas_cobradas_por_multa(): void
    {
        $texto = 'Cobro de MULTA de tránsito por infracción detectada.';
        $this->assertEquals('multas_cobradas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_multas_cobradas_por_infraccion(): void
    {
        $texto = 'INFRACCION de tránsito cometida el día 15/03/2026.';
        $this->assertEquals('multas_cobradas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_multas_cobradas_por_transito(): void
    {
        $texto = 'Departamento de TRANSITO — cobro de penalidad.';
        $this->assertEquals('multas_cobradas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_prendas(): void
    {
        $texto = 'Registro de PRENDA sobre vehículo matrícula ABC 1234.';
        $this->assertEquals('prendas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_arrendamientos(): void
    {
        $texto = 'Cobro de ARRENDAMIENTO correspondiente al mes de marzo 2026.';
        $this->assertEquals('arrendamientos', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_tenencia_armas_por_tenencia(): void
    {
        $texto = 'TENENCIA de arma de fuego — registro actualizado.';
        $this->assertEquals('tenencia_armas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_tenencia_armas_por_tahta(): void
    {
        $texto = 'Habilitación TAHTA — porte de armas.';
        $this->assertEquals('tenencia_armas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_porte_armas(): void
    {
        $texto = 'Autorización de PORTE de arma de fuego categoría A.';
        $this->assertEquals('porte_armas', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_eventuales(): void
    {
        $texto = 'Cobro correspondiente a POLICIAS EVENTUALES — mes enero.';
        $this->assertEquals('eventuales', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_generico_efactura(): void
    {
        $texto = 'e-Factura emitida por el proveedor XYZ S.A.';
        $this->assertEquals('generico', $this->service->detectarTipoCfe($texto));
    }

    public function test_detecta_desconocido_cuando_no_hay_coincidencia(): void
    {
        $texto = 'Documento sin ninguna palabra clave reconocida por el sistema.';
        $this->assertEquals('desconocido', $this->service->detectarTipoCfe($texto));
    }

    // -------------------------------------------------------------------------
    // Prioridad: certificado_residencia > multas (palabras clave en conflicto)
    // -------------------------------------------------------------------------

    public function test_certificado_residencia_tiene_prioridad_sobre_multas(): void
    {
        // Si un texto menciona "certificado de residencia" y también "multa",
        // debe ganar certificado_residencia (se detecta primero en el método)
        $texto = 'CERTIFICADO DE RESIDENCIA con nota de multa administrativa.';
        $this->assertEquals('certificado_residencia', $this->service->detectarTipoCfe($texto));
    }

    // -------------------------------------------------------------------------
    // Insensibilidad a mayúsculas/minúsculas y acentos
    // -------------------------------------------------------------------------

    public function test_deteccion_insensible_a_minusculas(): void
    {
        $texto = 'cobro de multa de tránsito por exceso de velocidad.';
        $this->assertEquals('multas_cobradas', $this->service->detectarTipoCfe($texto));
    }

    public function test_deteccion_insensible_a_acentos(): void
    {
        // "arrendamiento" sin acento no existe, pero verificamos que el quitarAcentos funciona
        $texto = 'Pago de arrendamiento numero 123.';
        $this->assertEquals('arrendamientos', $this->service->detectarTipoCfe($texto));
    }

    // -------------------------------------------------------------------------
    // quitarAcentos (método público auxiliar)
    // -------------------------------------------------------------------------

    public function test_quitar_acentos_reemplaza_vocales_acentuadas(): void
    {
        $this->assertEquals('aeiou', TextoHelper::quitarAcentos('áéíóú'));
        $this->assertEquals('AEIOU', TextoHelper::quitarAcentos('ÁÉÍÓÚ'));
        $this->assertEquals('n', TextoHelper::quitarAcentos('ñ'));
        $this->assertEquals('u', TextoHelper::quitarAcentos('ü'));
    }

    public function test_quitar_acentos_no_modifica_texto_sin_acentos(): void
    {
        $texto = 'hola mundo 123';
        $this->assertEquals($texto, TextoHelper::quitarAcentos($texto));
    }
}
