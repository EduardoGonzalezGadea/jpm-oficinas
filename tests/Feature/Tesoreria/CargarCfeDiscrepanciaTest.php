<?php

namespace Tests\Feature\Tesoreria;

use App\Livewire\Tesoreria\MultasCobradas\CargarCfe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CargarCfeDiscrepanciaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    private function datosExtraidosValidos(): array
    {
        return [
            'tipo_cfe' => 'e-Ticket',
            'serie' => 'A',
            'numero' => '9999',
            'fecha' => '12/08/2026',
            'cedula' => '1.234.567-8',
            'nombre' => 'TEST RECEPTOR',
            'domicilio' => 'CALLE TEST 123',
            'monto_total' => '1.500,00',
            'moneda' => 'UYU',
            'forma_pago' => 'Efectivo: 1.500,00',
            'referencias' => 'REF TEST',
            'adenda' => '',
            'adicional' => '',
            'items' => [
                ['detalle' => 'MULTA LEY 19.824 CORRESPONDE A INFRACCION DE TRANSITO', 'descripcion' => '', 'importe' => 1500.00],
            ],
        ];
    }

    /** @test */
    public function guarda_sin_discrepancia_cuando_suma_de_medios_coincide(): void
    {
        $this->withoutExceptionHandling();

        Livewire::test(CargarCfe::class)
            ->set('datosExtraidos', $this->datosExtraidosValidos())
            ->call('guardarRegistro')
            ->assertNotDispatched('swal:confirm-discrepancia-multas');

        $this->assertDatabaseHas('tes_multas_cobradas', [
            'recibo' => 'A-9999',
            'monto' => 1500.00,
        ]);
    }

    /** @test */
    public function emite_alerta_de_discrepancia_cuando_suma_de_medios_no_coincide(): void
    {
        $datos = $this->datosExtraidosValidos();
        $datos['forma_pago'] = 'Efectivo: 800,00 / Cheque: 300,00';

        Livewire::test(CargarCfe::class)
            ->set('datosExtraidos', $datos)
            ->call('guardarRegistro')
            ->assertDispatched('swal:confirm-discrepancia-multas');

        $this->assertDatabaseMissing('tes_multas_cobradas', [
            'recibo' => 'A-9999',
        ]);
    }

    /** @test */
    public function emite_alerta_de_discrepancia_cuando_no_hay_medios_de_pago(): void
    {
        $datos = $this->datosExtraidosValidos();
        $datos['forma_pago'] = 'SIN DATOS';

        Livewire::test(CargarCfe::class)
            ->set('datosExtraidos', $datos)
            ->call('guardarRegistro')
            ->assertDispatched('swal:confirm-discrepancia-multas');

        $this->assertDatabaseMissing('tes_multas_cobradas', [
            'recibo' => 'A-9999',
        ]);
    }

    /** @test */
    public function no_guarda_con_medios_editados_hasta_que_suma_coincida(): void
    {
        $datos = $this->datosExtraidosValidos();
        $datos['forma_pago'] = 'Efectivo: 800,00 / Cheque: 300,00';

        Livewire::test(CargarCfe::class)
            ->set('datosExtraidos', $datos)
            ->call('guardarRegistro')
            ->assertDispatched('swal:confirm-discrepancia-multas');

        // El usuario corrige los importes de los medios de pago hasta que sumen el total.
        Livewire::test(CargarCfe::class)
            ->set('datosExtraidos', $datos)
            ->set('mediosPagoForm', [
                ['nombre' => 'Efectivo', 'importe' => '1000,00'],
                ['nombre' => 'Cheque', 'importe' => '500,00'],
            ])
            ->call('guardarRegistro')
            ->assertNotDispatched('swal:confirm-discrepancia-multas');

        $this->assertDatabaseHas('tes_multas_cobradas', [
            'recibo' => 'A-9999',
            'monto' => 1500.00,
        ]);
    }
}
