<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Movimiento;
use App\Models\Tesoreria\Pendiente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Movimiento
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Movimiento>
 */
class MovimientoFactory extends Factory
{
    protected $model = Movimiento::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'relPendiente' => Pendiente::factory(),
            'fechaMovimientos' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'documentos' => 'DOC-' . strtoupper($this->faker->bothify('####')),
            'rendido' => null,
            'reintegrado' => null,
            'recuperado' => null,
        ];
    }

    /**
     * Estado para movimiento de un pendiente específico
     */
    public function paraPendiente(Pendiente $pendiente): static
    {
        return $this->state(fn (array $attributes) => [
            'relPendiente' => $pendiente->idPendientes,
        ]);
    }

    /**
     * Estado para movimiento rendido
     */
    public function rendido(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'rendido' => $monto,
        ]);
    }

    /**
     * Estado para movimiento reintegrado
     */
    public function reintegrado(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'reintegrado' => $monto,
        ]);
    }

    /**
     * Estado para movimiento recuperado
     */
    public function recuperado(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'recuperado' => $monto,
        ]);
    }

    /**
     * Estado para movimiento con fecha específica
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'fechaMovimientos' => $fecha,
        ]);
    }
}
