<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\CajaChica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para CajaChica
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\CajaChica>
 */
class CajaChicaFactory extends Factory
{
    protected $model = CajaChica::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'mes' => $this->faker->randomElement([
                'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
            ]),
            'anio' => $this->faker->numberBetween(2024, 2026),
            'montoCajaChica' => $this->faker->randomFloat(2, 3000, 10000),
        ];
    }

    /**
     * Estado para caja chica del mes actual
     */
    public function mesActual(): static
    {
        return $this->state(fn (array $attributes) => [
            'mes' => strtolower(now()->locale('es')->monthName),
            'anio' => now()->year,
        ]);
    }

    /**
     * Estado para caja chica con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'montoCajaChica' => $monto,
        ]);
    }

    /**
     * Estado para caja chica de un mes/año específico
     */
    public function enMes(string $mes, int $anio): static
    {
        return $this->state(fn (array $attributes) => [
            'mes' => $mes,
            'anio' => $anio,
        ]);
    }
}
