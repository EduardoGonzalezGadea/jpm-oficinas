<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Acreedor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Acreedor
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Acreedor>
 */
class AcreedorFactory extends Factory
{
    protected $model = Acreedor::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'acreedor' => $this->faker->company(),
        ];
    }

    /**
     * Estado para el acreedor BSE (Banco de Seguros del Estado)
     */
    public function bse(): static
    {
        return $this->state(fn (array $attributes) => [
            'acreedor' => 'Banco de Seguros del Estado',
        ]);
    }

    /**
     * Estado para acreedor con nombre específico
     */
    public function conNombre(string $nombre): static
    {
        return $this->state(fn (array $attributes) => [
            'acreedor' => $nombre,
        ]);
    }
}
