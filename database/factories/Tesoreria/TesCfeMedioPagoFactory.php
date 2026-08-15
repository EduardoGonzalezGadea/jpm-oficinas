<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfeMedioPago;
use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TesCfeMedioPago (Medios de pago de CFE)
 */
class TesCfeMedioPagoFactory extends Factory
{
    protected $model = TesCfeMedioPago::class;

    public function definition(): array
    {
        return [
            'tes_cfe_id' => TesCfe::factory(),
            'medio_pago_id' => MedioDePago::factory(),
            'monto' => $this->faker->randomFloat(2, 500, 10000),
        ];
    }

    /**
     * Medio de pago para un CFE específico
     */
    public function paraCfe(TesCfe $cfe): static
    {
        return $this->state(fn (array $attributes) => [
            'tes_cfe_id' => $cfe->id,
        ]);
    }

    /**
     * Medio de pago con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'monto' => $monto,
        ]);
    }

    /**
     * Medio de pago específico
     */
    public function conMedio(MedioDePago $medio): static
    {
        return $this->state(fn (array $attributes) => [
            'medio_pago_id' => $medio->id,
        ]);
    }
}
