<?php

namespace Tests\Feature\Tesoreria\CFE;

use App\DTOs\CfeExtraccionDto;
use Tests\TesoreriaTestCase;

/**
 * Tests de Extracción de Datos de CFE
 * 
 * Cubre:
 * - CfeExtraccionDto
 * - Conversión de arrays a DTO
 * - Conversión de DTO a array
 * - Merge de datos
 * - Valores por defecto
 */
class CfeExtractionTest extends TesoreriaTestCase
{
    public function test_puede_crear_dto_desde_array(): void
    {
        $data = [
            'tipo_cfe' => 'Multas',
            'serie' => 'A',
            'numero' => '123456',
            'fecha' => '14/08/2026',
            'monto' => 5000.00,
            'moneda' => 'UYU',
            'cedula' => '12345678',
            'nombre' => 'Juan Pérez',
            'items' => [],
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Multas', $dto->tipoCfe);
        $this->assertEquals('A', $dto->serie);
        $this->assertEquals('123456', $dto->numero);
        $this->assertFloatEquals(5000.00, $dto->monto);
        $this->assertEquals('UYU', $dto->moneda);
    }

    public function test_dto_maneja_campos_opcionales(): void
    {
        $data = [
            'tipo_cfe' => 'Certificados',
            'monto' => 1000.00,
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Certificados', $dto->tipoCfe);
        $this->assertNull($dto->serie);
        $this->assertNull($dto->numero);
        $this->assertNull($dto->fecha);
    }

    public function test_dto_tiene_valores_por_defecto(): void
    {
        $data = [];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('No detectado', $dto->tipoCfe);
        $this->assertEquals('UYU', $dto->moneda);
        $this->assertFloatEquals(0.0, $dto->monto);
        $this->assertEquals([], $dto->items);
    }

    public function test_dto_convierte_a_array(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'Multas',
            serie: 'A',
            numero: '123456',
            fecha: '14/08/2026',
            monto: 5000.00,
            moneda: 'UYU',
            cedula: '12345678',
            nombre: 'Juan Pérez',
            domicilio: null,
            montoTotal: 5000.00,
            formaPago: 'contado',
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalleCompleto: null,
            tipoCfeCodigo: null,
            extractorVersion: '1.0'
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Multas', $array['tipo_cfe']);
        $this->assertEquals('A', $array['serie']);
        $this->assertEquals('123456', $array['numero']);
        $this->assertFloatEquals(5000.00, $array['monto']);
    }

    public function test_dto_es_json_serializable(): void
    {
        $dto = new CfeExtraccionDto(
            tipoCfe: 'Multas',
            serie: 'A',
            numero: '123456',
            fecha: '14/08/2026',
            monto: 5000.00,
            moneda: 'UYU',
            cedula: null,
            nombre: null,
            domicilio: null,
            montoTotal: null,
            formaPago: null,
            adicional: null,
            adenda: null,
            referencias: null,
            items: [],
            detalleCompleto: null,
            tipoCfeCodigo: null,
            extractorVersion: null
        );

        $json = json_encode($dto);

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals('Multas', $decoded['tipo_cfe']);
    }

    public function test_dto_maneja_items(): void
    {
        $items = [
            ['descripcion' => 'Item 1', 'monto' => 1000],
            ['descripcion' => 'Item 2', 'monto' => 2000],
        ];

        $dto = CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'Multas',
            'monto' => 3000.00,
            'items' => $items,
        ]);

        $this->assertCount(2, $dto->items);
        $this->assertEquals('Item 1', $dto->items[0]['descripcion']);
    }

    public function test_dto_maneja_datos_receptor(): void
    {
        $data = [
            'tipo_cfe' => 'eFactura',
            'monto' => 5000.00,
            'receptor_documento' => '211234560018',
            'receptor_nombre' => 'Empresa S.A.',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('211234560018', $dto->receptorDocumento);
        $this->assertEquals('Empresa S.A.', $dto->receptorNombre);
    }

    public function test_dto_with_extractor_version(): void
    {
        $dto = CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'Multas',
            'monto' => 1000.00,
        ]);

        $dtoConVersion = $dto->withExtractorVersion('2.0');

        $this->assertNull($dto->extractorVersion);
        $this->assertEquals('2.0', $dtoConVersion->extractorVersion);
    }

    public function test_dto_merge_actualiza_campos(): void
    {
        $dto = CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'Multas',
            'serie' => 'A',
            'numero' => '123456',
            'monto' => 5000.00,
        ]);

        $dtoMerged = $dto->merge([
            'serie' => 'B',
            'monto' => 6000.00,
        ]);

        $this->assertEquals('A', $dto->serie); // Original no cambia
        $this->assertEquals('B', $dtoMerged->serie);
        $this->assertFloatEquals(6000.00, $dtoMerged->monto);
    }

    public function test_dto_maneja_campos_certificados(): void
    {
        $data = [
            'tipo_cfe' => 'Certificados',
            'monto' => 500.00,
            'cedula_receptor' => '12345678',
            'nombre_receptor' => 'Juan Pérez',
            'cedula_titular' => '87654321',
            'nombre_titular' => 'María González',
            'retira_es_titular' => false,
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('12345678', $dto->cedulaReceptor);
        $this->assertEquals('Juan Pérez', $dto->nombreReceptor);
        $this->assertEquals('87654321', $dto->cedulaTitular);
        $this->assertEquals('María González', $dto->nombreTitular);
        $this->assertFalse($dto->retiraEsTitular);
    }

    public function test_dto_retira_es_titular_por_defecto(): void
    {
        $dto = CfeExtraccionDto::fromArray([
            'tipo_cfe' => 'Certificados',
            'monto' => 500.00,
        ]);

        $this->assertTrue($dto->retiraEsTitular);
    }

    public function test_dto_maneja_referencias_contables(): void
    {
        $data = [
            'tipo_cfe' => 'eFactura',
            'monto' => 10000.00,
            'ingreso_contabilidad' => 'ING-2026-001',
            'orden_cobro' => 'OC-2026-001',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('ING-2026-001', $dto->ingresoContabilidad);
        $this->assertEquals('OC-2026-001', $dto->ordenCobro);
    }

    public function test_dto_maneja_descripcion_y_tramite(): void
    {
        $data = [
            'tipo_cfe' => 'Armas',
            'monto' => 2500.00,
            'descripcion' => 'Renovación de permiso',
            'tramite' => 'RENOV-2026-123',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Renovación de permiso', $dto->descripcion);
        $this->assertEquals('RENOV-2026-123', $dto->tramite);
    }

    public function test_dto_maneja_telefono(): void
    {
        $data = [
            'tipo_cfe' => 'Certificados',
            'monto' => 500.00,
            'telefono' => '099123456',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('099123456', $dto->telefono);
    }

    public function test_dto_monto_total_fallback(): void
    {
        $data = [
            'tipo_cfe' => 'Multas',
            'monto_total' => 5000.00,
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        // Si no hay 'monto', debe usar 'monto_total'
        $this->assertFloatEquals(5000.00, $dto->monto);
        $this->assertFloatEquals(5000.00, $dto->montoTotal);
    }

    public function test_dto_maneja_detalle_y_detalle_completo(): void
    {
        $data = [
            'tipo_cfe' => 'Multas',
            'monto' => 3000.00,
            'detalle' => 'Resumen del documento',
            'detalle_completo' => 'Detalle completo con todos los campos extraídos del PDF',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Resumen del documento', $dto->detalle);
        $this->assertEquals('Detalle completo con todos los campos extraídos del PDF', $dto->detalleCompleto);
    }

    public function test_dto_maneja_tipo_cfe_codigo(): void
    {
        $data = [
            'tipo_cfe' => 'eFactura',
            'tipo_cfe_codigo' => '111',
            'monto' => 5000.00,
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('111', $dto->tipoCfeCodigo);
    }

    public function test_dto_maneja_forma_pago(): void
    {
        $data = [
            'tipo_cfe' => 'Multas',
            'monto' => 3000.00,
            'forma_pago' => 'credito',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('credito', $dto->formaPago);
    }

    public function test_dto_maneja_referencias_y_adenda(): void
    {
        $data = [
            'tipo_cfe' => 'eFactura',
            'monto' => 7500.00,
            'referencias' => 'Factura anterior: 12345',
            'adenda' => 'Información adicional del comprobante',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Factura anterior: 12345', $dto->referencias);
        $this->assertEquals('Información adicional del comprobante', $dto->adenda);
    }

    public function test_dto_maneja_domicilio_y_adicional(): void
    {
        $data = [
            'tipo_cfe' => 'Multas',
            'monto' => 2000.00,
            'domicilio' => 'Av. 18 de Julio 1234',
            'adicional' => 'Apto 501',
        ];

        $dto = CfeExtraccionDto::fromArray($data);

        $this->assertEquals('Av. 18 de Julio 1234', $dto->domicilio);
        $this->assertEquals('Apto 501', $dto->adicional);
    }
}
