<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\LbTipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para LbTipo
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\LbTipo>
 */
class LbTipoFactory extends Factory
{
    protected $model = LbTipo::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Entrada',
            'signo' => 1,
        ];
    }

    /**
     * Estado para tipo Entrada
     */
    public function entrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Entrada',
            'signo' => 1,
        ]);
    }

    /**
     * Estado para tipo Salida
     */
    public function salida(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Salida',
            'signo' => -1,
        ]);
    }

    /**
     * Estado para tipo Redistribución
     */
    public function redistribucion(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Redistribución',
            'signo' => 0,
        ]);
    }
}
