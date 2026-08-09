<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Models\Tesoreria\MedioDePago;
use App\Services\Tesoreria\MedioPagoService;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MedioPagoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('tes_medio_de_pagos')) {
            Schema::create('tes_medio_de_pagos', function ($table) {
                $table->id();
                $table->string('nombre', 100);
                $table->string('nombre_corto', 100)->nullable();
                $table->string('descripcion', 255)->nullable();
                $table->boolean('activo')->default(true);
                $table->boolean('contado')->default(false);
                $table->boolean('es_libro_diario')->default(true);
                $table->boolean('es_recaudacion')->default(false);
                $table->integer('orden')->default(0);
                $table->string('codigo_soniar', 50)->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->unsignedInteger('deleted_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!MedioDePago::where('nombre', 'Efectivo')->exists()) {
            MedioDePago::insert([
                [
                    'nombre' => 'Efectivo',
                    'nombre_corto' => 'Efectivo',
                    'descripcion' => 'Dinero físico',
                    'contado' => true,
                    'es_libro_diario' => true,
                    'es_recaudacion' => true,
                    'orden' => 1,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Cheque',
                    'nombre_corto' => 'Cheque',
                    'descripcion' => 'Cheque bancario',
                    'contado' => false,
                    'es_libro_diario' => true,
                    'es_recaudacion' => true,
                    'orden' => 2,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Transferencia Bancaria',
                    'nombre_corto' => 'Transferencia',
                    'descripcion' => 'Transferencia entre cuentas',
                    'contado' => false,
                    'es_libro_diario' => true,
                    'es_recaudacion' => true,
                    'orden' => 3,
                    'activo' => true,
                ],
                [
                    'nombre' => 'Tarjeta de Débito',
                    'nombre_corto' => 'Débito (POS)',
                    'descripcion' => 'Tarjeta de débito terminal POS',
                    'contado' => false,
                    'es_libro_diario' => true,
                    'es_recaudacion' => true,
                    'orden' => 4,
                    'activo' => true,
                ],
            ]);
        }
    }

    public function test_medios_combinados_se_normalizan_alfabeticamente()
    {
        $service = new MedioPagoService();

        $medio1 = 'EFECTIVO / TARJETA DE DÉBITO';
        $medio2 = 'TARJETA DE DÉBITO / EFECTIVO';

        $normalizado1 = $service->normalizar($medio1);
        $normalizado2 = $service->normalizar($medio2);

        $this->assertEquals($normalizado1, $normalizado2);
        $this->assertEquals('Efectivo/Tarjeta de Débito', $normalizado1);
    }

    public function test_normalizacion_medios_con_valores()
    {
        $service = new MedioPagoService();

        $medio = 'TARJETA DE DÉBITO:500 / EFECTIVO:1000';
        $normalizado = $service->normalizar($medio);

        $this->assertEquals('Efectivo:1000.00/Tarjeta de Débito:500.00', $normalizado);
    }

    public function test_validacion_formato_valido()
    {
        $service = new MedioPagoService();

        $mediosValidos = [
            'EFECTIVO',
            'TARJETA',
            'CHEQUE',
            'EFECTIVO/TARJETA',
            'EFECTIVO:1000/TARJETA:500',
            'TRANSFERENCIA:2500',
            'PAYPAL',
            'SIN DATOS',
        ];

        foreach ($mediosValidos as $medio) {
            $this->assertTrue($service->validarFormato($medio));
        }
    }

    public function test_validacion_formato_invalido()
    {
        $service = new MedioPagoService();

        $mediosInvalidos = [
            'EFECTIVO|TARJETA',
            'EFECTIVO:TARJETA',
            'EFECTIVO/1000',
            'TARJETA:abc',
        ];

        foreach ($mediosInvalidos as $medio) {
            $this->assertFalse($service->validarFormato($medio));
        }
    }

    public function test_validacion_consistencia_valores()
    {
        $service = new MedioPagoService();

        $this->assertTrue($service->validarConsistencia('EFECTIVO:1000/TARJETA:500', 1500));
        $this->assertFalse($service->validarConsistencia('EFECTIVO:1000/TARJETA:500', 2000));
    }

    public function test_validacion_y_normalizacion()
    {
        $service = new MedioPagoService();

        $medio = 'TARJETA DE DÉBITO / EFECTIVO';
        $normalizado = $service->validarYNormalizar($medio);

        $this->assertEquals('Efectivo/Tarjeta de Débito', $normalizado);
    }
}
