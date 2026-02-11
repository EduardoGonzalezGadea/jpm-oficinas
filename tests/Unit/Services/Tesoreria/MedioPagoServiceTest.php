<?php

namespace Tests\Unit\Services\Tesoreria;

use App\Services\Tesoreria\MedioPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MedioPagoServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test que verifica que los medios de pago combinados se normalizan alfabéticamente
     */
    public function test_medios_combinados_se_normalizan_alfabeticamente()
    {
        $service = new MedioPagoService();

        $medio1 = 'EFECTIVO / TARJETA DE DÉBITO';
        $medio2 = 'TARJETA DE DÉBITO / EFECTIVO';

        $normalizado1 = $service->normalizar($medio1);
        $normalizado2 = $service->normalizar($medio2);

        $this->assertEquals($normalizado1, $normalizado2);
        $this->assertEquals('EFECTIVO/TARJETA DE DÉBITO', $normalizado1);
    }

    /**
     * Test que verifica la normalización de medios de pago con valores
     */
    public function test_normalizacion_medios_con_valores()
    {
        $service = new MedioPagoService();

        $medio = 'TARJETA DE DÉBITO:500 / EFECTIVO:1000';
        $normalizado = $service->normalizar($medio);

        $this->assertEquals('EFECTIVO:1000.00/TARJETA DE DÉBITO:500.00', $normalizado);
    }

    /**
     * Test que verifica la validación de formatos válidos
     */
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

    /**
     * Test que verifica la validación de formatos inválidos
     */
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

    /**
     * Test que verifica la validación de consistencia de valores
     */
    public function test_validacion_consistencia_valores()
    {
        $service = new MedioPagoService();

        $this->assertTrue($service->validarConsistencia('EFECTIVO:1000/TARJETA:500', 1500));
        $this->assertFalse($service->validarConsistencia('EFECTIVO:1000/TARJETA:500', 2000));
    }

    /**
     * Test que verifica la validación y normalización completa
     */
    public function test_validacion_y_normalizacion()
    {
        $service = new MedioPagoService();

        $medio = 'TARJETA DE DÉBITO / EFECTIVO';
        $normalizado = $service->validarYNormalizar($medio);

        $this->assertEquals('EFECTIVO/TARJETA DE DÉBITO', $normalizado);
    }
}
