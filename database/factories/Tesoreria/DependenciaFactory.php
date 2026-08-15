<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Dependencia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Dependencia
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Dependencia>
 */
class DependenciaFactory extends Factory
{
    protected $model = Dependencia::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'dependencias' => $this->faker->randomElement([
                'Dirección General',
                'Departamento Administrativo',
                'Departamento Financiero',
                'Recursos Humanos',
                'Sistemas',
                'Compras',
                'Mantenimiento',
                'Servicios Generales',
            ]),
        ];
    }

    /**
     * Estado para dependencia con nombre específico
     */
    public function conNombre(string $nombre): static
    {
        return $this->state(fn (array $attributes) => [
            'dependencias' => $nombre,
        ]);
    }
}
