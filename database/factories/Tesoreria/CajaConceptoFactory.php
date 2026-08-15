<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\CajaConcepto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para CajaConcepto
 */
class CajaConceptoFactory extends Factory
{
    protected $model = CajaConcepto::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->randomElement([
                'Multas de Tránsito',
                'Certificados de Residencia',
                'Armas y Explosivos',
                'Arrendamientos',
                'Prenda con Registro',
            ]),
            'codigo' => $this->faker->unique()->numerify('CONC-###'),
            'descripcion' => $this->faker->sentence(),
            'activo' => true,
        ];
    }

    /**
     * Concepto para multas
     */
    public function multas(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Multas de Tránsito',
            'codigo' => 'MULTAS',
        ]);
    }

    /**
     * Concepto para certificados
     */
    public function certificados(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Certificados de Residencia',
            'codigo' => 'CERT',
        ]);
    }

    /**
     * Concepto inactivo
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
