<?php

namespace Tests\Unit\Models\Tesoreria;

use App\Models\Tesoreria\Pago;
use Tests\TestCase;

class PagoTest extends TestCase
{
    public function test_no_tiene_datos_rendicion_ni_recuperacion_en_pago_nuevo(): void
    {
        $pago = new Pago([
            'montoPagos' => 1000,
            'rendidoPagos' => null,
            'reintegradoPagos' => null,
            'recuperadoPagos' => 0,
        ]);

        $this->assertFalse($pago->tieneDatosRendicion());
        $this->assertFalse($pago->tieneDatosRecuperacion());
    }

    public function test_detecta_datos_de_rendicion(): void
    {
        $pago = new Pago([
            'rendidoPagos' => 800,
            'reintegradoPagos' => 200,
        ]);

        $this->assertTrue($pago->tieneDatosRendicion());
    }

    public function test_detecta_datos_de_recuperacion(): void
    {
        $pago = new Pago([
            'recuperadoPagos' => 100,
            'ingresoPagos' => '12345',
        ]);

        $this->assertTrue($pago->tieneDatosRecuperacion());
    }

    public function test_puede_recuperar_solo_con_rendicion_sin_recuperacion(): void
    {
        $pagoRendido = new Pago([
            'rendidoPagos' => 800,
            'reintegradoPagos' => 200,
        ]);

        $pagoRecuperado = new Pago([
            'rendidoPagos' => 800,
            'reintegradoPagos' => 200,
            'recuperadoPagos' => 800,
            'ingresoPagos' => '12345',
        ]);

        $this->assertTrue($pagoRendido->puedeRecuperar());
        $this->assertFalse($pagoRecuperado->puedeRecuperar());
    }
}
