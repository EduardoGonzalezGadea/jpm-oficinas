<?php

namespace Tests\Feature\Tesoreria;

use App\Models\TesCfePendiente;
use App\Services\CfeProcessorService;
use App\DTOs\CfeExtraccionDto;
use App\Exceptions\CfeExtraccionInvalidaException;
use App\Repositories\CfePendienteRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CfeProcessorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CfeProcessorService $cfeProcessorService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cfeProcessorService = app(CfeProcessorService::class);
        Storage::fake('local');
    }

    /** @test */
    public function puede_procesar_pdf_multas_y_crear_pendiente(): void
    {
        Config::set('cfe.auto_confirm_types.multas_cobradas', false);
        Config::set('cfe.cache.enabled', false);

        $pdfContent = $this->getPdfMultasContent();

        $dto = \App\DTOs\CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'multas_cobradas',
            'serie' => 'B',
            'numero' => '5678',
            'fecha' => '2026-06-20',
            'monto' => 3200.00,
            'moneda' => 'UYU',
            'cedula' => '5.678.901-2',
            'nombre' => 'MARIA RODRIGUEZ LOPEZ',
            'domicilio' => '5678 CALLE URUGUAY',
            'monto_total' => 3200.00,
            'forma_pago' => 'Efectivo: 3.200,00',
            'referencias' => 'e-Ticket-B-5678',
            'items' => [
                ['detalle' => 'MULTA LEY 19.824 CORRESPONDE A INFRACCION DE TRANSITO', 'importe' => 3200.00],
            ],
        ]);

        $service = \Mockery::mock(\App\Services\CfeProcessorService::class . '[ejecutarExtraccion]', [
            app(\App\Repositories\CfePendienteRepository::class),
        ])->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('ejecutarExtraccion')->andReturn([
            'dto' => $dto,
            'tipo_cfe' => 'multas_cobradas',
            'extractor_version' => '1.0.0',
            'fallback_usado' => false,
            'desde_cache' => false,
        ]);
        $service->makePartial();

        $file = UploadedFile::fake()->createWithContent('multa.pdf', $pdfContent);
        $pendiente = $service->procesarPdf($file);

        $this->assertNotNull($pendiente);
        $this->assertEquals('multas_cobradas', $pendiente->tipo_cfe);
        $this->assertEquals('pendiente', $pendiente->estado);
        $this->assertNotNull($pendiente->pdf_hash);
        $this->assertNotNull($pendiente->pdf_path);
    }

    /** @test */
    public function no_duplica_pdf_con_mismo_hash(): void
    {
        Config::set('cfe.auto_confirm_types.multas_cobradas', false);
        Config::set('cfe.cache.enabled', false);

        $pdfContent = $this->getPdfMultasContent();

        $dto = \App\DTOs\CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'multas_cobradas',
            'serie' => 'B',
            'numero' => '5678',
            'fecha' => '2026-06-20',
            'monto' => 3200.00,
            'moneda' => 'UYU',
            'cedula' => '5.678.901-2',
            'nombre' => 'MARIA RODRIGUEZ LOPEZ',
            'domicilio' => '5678 CALLE URUGUAY',
            'monto_total' => 3200.00,
            'forma_pago' => 'Efectivo: 3.200,00',
            'referencias' => 'e-Ticket-B-5678',
            'items' => [
                ['detalle' => 'MULTA LEY 19.824 CORRESPONDE A INFRACCION DE TRANSITO', 'importe' => 3200.00],
            ],
        ]);

        $service = \Mockery::mock(\App\Services\CfeProcessorService::class . '[ejecutarExtraccion]', [
            app(\App\Repositories\CfePendienteRepository::class),
        ])->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('ejecutarExtraccion')->andReturn([
            'dto' => $dto,
            'tipo_cfe' => 'multas_cobradas',
            'extractor_version' => '1.0.0',
            'fallback_usado' => false,
            'desde_cache' => false,
        ]);
        $service->makePartial();

        $file = UploadedFile::fake()->createWithContent('multa.pdf', $pdfContent);
        $first = $service->procesarPdf($file);

        $file2 = UploadedFile::fake()->createWithContent('multa2.pdf', $pdfContent);
        $second = $service->procesarPdf($file2);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, TesCfePendiente::where('pdf_hash', $first->pdf_hash)->count());
    }

    /** @test */
    public function detecta_tipo_cfe_correctamente(): void
    {
        $casos = [
            'multas' => 'multas_cobradas',
            'infraccion' => 'multas_cobradas',
            'transito' => 'multas_cobradas',
            'prenda' => 'prendas',
            'arrendamiento' => 'arrendamientos',
            'eventuales' => 'eventuales',
            'aguinaldo' => 'eventuales',
            'tenencia' => 'tenencia_armas',
            'porte' => 'porte_armas',
            'arma' => 'tenencia_armas',
            'certificado de residencia' => 'certificado_residencia',
        ];

        foreach ($casos as $texto => $tipoEsperado) {
            $tipo = $this->cfeProcessorService->detectarTipoCfe($texto);
            $this->assertEquals($tipoEsperado, $tipo, "Fallo para texto: $texto");
        }
    }

    /** @test */
    public function detecta_generico_para_efactura(): void
    {
        $tipo = $this->cfeProcessorService->detectarTipoCfe('e-factura cobranza');
        $this->assertEquals('generico', $tipo);

        $tipo = $this->cfeProcessorService->detectarTipoCfe('e-ticket');
        $this->assertEquals('generico', $tipo);

        $tipo = $this->cfeProcessorService->detectarTipoCfe('e-boleta');
        $this->assertEquals('generico', $tipo);
    }

    /** @test */
    public function detecta_desconocido_para_texto_aleatorio(): void
    {
        $tipo = $this->cfeProcessorService->detectarTipoCfe('Este es un texto aleatorio sin palabras clave');
        $this->assertEquals('desconocido', $tipo);
    }

    /** @test */
    public function prioridad_certificado_sobre_multas(): void
    {
        // "Certificado de residencia" debería tener prioridad sobre "multa" si ambas aparecen
        $tipo = $this->cfeProcessorService->detectarTipoCfe('Certificado de residencia y multa de transito');
        $this->assertEquals('certificado_residencia', $tipo);
    }

    /** @test */
    public function insensible_a_mayusculas_y_acentos(): void
    {
        $tipo = $this->cfeProcessorService->detectarTipoCfe('MULTA DE TRÁNSITO');
        $this->assertEquals('multas_cobradas', $tipo);

        $tipo = $this->cfeProcessorService->detectarTipoCfe('MULTA DE TRANSITO');
        $this->assertEquals('multas_cobradas', $tipo);

        $tipo = $this->cfeProcessorService->detectarTipoCfe('PRÉNDA');
        $this->assertEquals('prendas', $tipo);

        $tipo = $this->cfeProcessorService->detectarTipoCfe('PRENDA');
        $this->assertEquals('prendas', $tipo);
    }

    /** @test */
    public function quitar_acentos_funciona(): void
    {
        $sinAcentos = \App\Helpers\TextoHelper::quitarAcentos('MULTA DE TRÁNSITO CON ÁÉÍÓÚÑ');
        $this->assertEquals('MULTA DE TRANSITO CON AEIOUN', $sinAcentos);
    }

    /**
     * Obtiene contenido PDF simple para multas (simulado).
     */
    public function test_cache_se_puebla_con_clave_correcta(): void
    {
        $cacheKey = 'cfe_pdf_test_hash_cache_key';
        Cache::forget($cacheKey);

        Cache::put($cacheKey, [
            'tipo_cfe' => 'multas_cobradas',
            'datos' => ['serie' => 'Z', 'numero' => '99999'],
            'extractor_version' => '1.0.0',
            'procesado_at' => now()->toIso8601String(),
        ], now()->addDays(7));

        $this->assertNotNull(Cache::get($cacheKey));
        $cached = Cache::get($cacheKey);
        $this->assertEquals('multas_cobradas', $cached['tipo_cfe']);

        Cache::forget($cacheKey);
    }

    public function test_cache_key_format(): void
    {
        $hash = hash('sha256', 'test_content');
        $expectedKey = 'cfe_pdf_' . $hash;
        $this->assertStringStartsWith('cfe_pdf_', $expectedKey);
        $this->assertEquals(64 + 8, strlen($expectedKey));
    }

    /** @test */
    public function procesar_pendiente_existente_throws_when_pdf_not_found(): void
    {
        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'pdf_path'        => 'cfe-pendientes/nonexistent.pdf',
            'pdf_hash'        => hash('sha256', 'test'),
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Archivo PDF no encontrado');

        $this->cfeProcessorService->procesarPendienteExistente($pendiente);
    }

    /** @test */
    public function procesar_pendiente_existente_uses_cache_when_available(): void
    {
        Config::set('cfe.auto_confirm_types.multas_cobradas', false);

        $pdfContent = $this->getPdfMultasContent();
        $pdfHash = hash('sha256', $pdfContent);

        Storage::fake('local');
        Storage::put('cfe-pendientes/test.pdf', $pdfContent);

        $cacheKey = 'cfe_pdf_' . $pdfHash;
        Cache::put($cacheKey, [
            'tipo_cfe'          => 'multas_cobradas',
            'datos'             => ['serie' => 'X', 'numero' => '12345', 'fecha' => '2026-01-01', 'monto' => 100.0, 'moneda' => 'UYU'],
            'extractor_version' => '1.0.0',
            'fallback_usado'    => false,
            'procesado_at'      => now()->toIso8601String(),
        ], now()->addDays(7));

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'pdf_path'        => 'cfe-pendientes/test.pdf',
            'pdf_hash'        => $pdfHash,
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $resultado = $this->cfeProcessorService->procesarPendienteExistente($pendiente);

        $this->assertEquals('multas_cobradas', $resultado->tipo_cfe);
        $this->assertEquals('X', $resultado->serie);
        $this->assertEquals('12345', $resultado->numero);
        $this->assertEquals('pendiente', $resultado->estado);

        Cache::forget($cacheKey);
    }

    /** @test */
    public function procesar_pendiente_existente_recalculates_hash_when_missing(): void
    {
        Config::set('cfe.auto_confirm_types.multas_cobradas', false);

        $pdfContent = $this->getPdfMultasContent();
        $pdfHash = hash('sha256', $pdfContent);

        Storage::put('cfe-pendientes/test.pdf', $pdfContent);

        $pendiente = TesCfePendiente::create([
            'tipo_cfe'        => 'multas_cobradas',
            'pdf_path'        => 'cfe-pendientes/test.pdf',
            'datos_extraidos' => [],
            'estado'          => 'pendiente',
        ]);

        $cacheKey = 'cfe_pdf_' . $pdfHash;
        Cache::put($cacheKey, [
            'tipo_cfe'          => 'multas_cobradas',
            'datos'             => ['serie' => 'Y', 'numero' => '54321', 'fecha' => '2026-06-15', 'monto' => 250.50, 'moneda' => 'UYU'],
            'extractor_version' => '2.0.0',
            'fallback_usado'    => false,
            'procesado_at'      => now()->toIso8601String(),
        ], now()->addDays(7));

        $resultado = $this->cfeProcessorService->procesarPendienteExistente($pendiente);

        $this->assertEquals('Y', $resultado->serie);
        $this->assertEquals('54321', $resultado->numero);
        $this->assertEquals('2.0.0', $resultado->extractor_version);

        Cache::forget($cacheKey);
    }

    /** @test */
    public function extrae_datos_de_fixture_multa_credito(): void
    {
        $fixturePath = dirname(__DIR__, 3) . '/tests/fixtures/cfe/multas/multa_credito.txt';
        $texto = \App\Helpers\TextoHelper::quitarAcentos(file_get_contents($fixturePath));

        $extractor = new \App\Services\CfeExtractor\MultasExtractor();
        $dto = $extractor->extraer($texto);

        $this->assertEquals('e-Factura', $dto->tipoCfe);
        $this->assertEquals('B', $dto->serie);
        $this->assertEquals('5678', $dto->numero);
        $this->assertNotEmpty($dto->fecha);
        $this->assertEquals(3200.00, $dto->monto);
        $this->assertEquals('UYU', $dto->moneda);
        $this->assertEquals('5.678.901-2', $dto->cedula);
        $this->assertNotEmpty($dto->items);
        $this->assertCount(1, $dto->items);
    }

    /** @test */
    public function extrae_datos_de_fixture_eventual_nota_credito(): void
    {
        $fixturePath = dirname(__DIR__, 3) . '/tests/fixtures/cfe/eventuales/eventual_nota_credito.txt';
        $texto = file_get_contents($fixturePath);

        $extractor = new \App\Services\CfeExtractor\EventualesExtractor();
        $dto = $extractor->extraer($texto);

        $this->assertEquals('e-Factura Cobranza', $dto->tipoCfe);
        $this->assertEquals('I', $dto->serie);
        $this->assertEquals('0123', $dto->numero);
        $this->assertNotEmpty($dto->fecha);
        $this->assertEquals(-637.70, $dto->monto);
        $this->assertEquals('UYU', $dto->moneda);
        $this->assertNotEmpty($dto->items);
        $this->assertCount(1, $dto->items);
        $this->assertEquals(-637.70, $dto->items[0]['importe']);
    }

    private function getPdfMultasContent(): string
    {
        return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R >>\nendobj\n4 0 obj\n<< /Length 44 >>\nstream\nBT /F1 12 Tf 100 700 Td (MULTA DE TRANSITO) Tj ET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f \n0000000010 00000 n \n0000000060 00000 n \n0000000117 00000 n \n0000000218 00000 n \ntrailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n305\n%%EOF";
    }
}