<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfeMedioPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class TesCfeMedioPagoFactory extends Factory
{
    protected $model = TesCfeMedioPago::class;

    public function definition(): array
    {
        return [
            'medio_pago_tipo' => $this->faker->randomElement(['Efectivo', 'Cheque', 'Transferencia Bancaria', 'Tarjeta de Débito']),
            'medio_pago_valor' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
