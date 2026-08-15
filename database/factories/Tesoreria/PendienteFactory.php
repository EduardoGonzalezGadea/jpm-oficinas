<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Dependencia;
use App\Models\Tesoreria\Pendiente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Pendiente
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Pendiente>
 */
class PendienteFactory extends Factory
{
    protected $model = Pendiente::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'relCajaChica' => CajaChica::factory(),
            'pendiente' => 'PEND-' . strtoupper($this->faker->unique()->bothify('####')),
            'fechaPendientes' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'relDependencia' => Dependencia::factory(),
            'montoPendientes' => $this->faker->randomFloat(2, 50, 3000),
        ];
    }

    /**
     * Estado para pendiente vinculado a una caja chica específica
     */
    public function paraCajaChica(CajaChica $cajaChica): static
    {
        return $this->state(fn (array $attributes) => [
            'relCajaChica' => $cajaChica->idCajaChica,
        ]);
    }

    /**
     * Estado para pendiente de una dependencia específica
     */
    public function paraDependencia(Dependencia $dependencia): static
    {
        return $this->state(fn (array $attributes) => [
            'relDependencia' => $dependencia->idDependencias,
        ]);
    }

    /**
     * Estado para pendiente con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'montoPendientes' => $monto,
        ]);
    }

    /**
     * Estado para pendiente con fecha específica
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'fechaPendientes' => $fecha,
        ]);
    }
}
