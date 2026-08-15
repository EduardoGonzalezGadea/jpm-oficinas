<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\MedioDePago;
use App\Models\Tesoreria\TesMultasCobradas;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TesMultasCobradas
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\TesMultasCobradas>
 */
class TesMultasCobradasFactory extends Factory
{
    protected $model = TesMultasCobradas::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'recibo' => 'REC-' . strtoupper($this->faker->unique()->bothify('########')),
            'cedula' => $this->faker->numberBetween(10000000, 99999999) . '-' . $this->faker->numberBetween(0, 9),
            'nombre' => $this->faker->name(),
            'domicilio' => $this->faker->streetAddress(),
            'adicional' => $this->faker->optional()->sentence(),
            'fecha' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'monto' => $this->faker->randomFloat(2, 500, 10000),
            'forma_pago' => $this->faker->randomElement(['contado', 'credito']),
            'medio_pago_id' => MedioDePago::factory(),
            'referencias' => null,
            'adenda' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Estado para multa con recibo específico
     */
    public function conRecibo(string $recibo): static
    {
        return $this->state(fn (array $attributes) => [
            'recibo' => $recibo,
        ]);
    }

    /**
     * Estado para multa con cédula específica
     */
    public function conCedula(string $cedula): static
    {
        return $this->state(fn (array $attributes) => [
            'cedula' => $cedula,
        ]);
    }

    /**
     * Estado para multa con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'monto' => $monto,
        ]);
    }

    /**
     * Estado para multa de contado
     */
    public function contado(): static
    {
        return $this->state(fn (array $attributes) => [
            'forma_pago' => 'contado',
        ]);
    }

    /**
     * Estado para multa a crédito
     */
    public function credito(): static
    {
        return $this->state(fn (array $attributes) => [
            'forma_pago' => 'credito',
        ]);
    }

    /**
     * Estado para multa con medio de pago específico
     */
    public function conMedioDePago(MedioDePago $medio): static
    {
        return $this->state(fn (array $attributes) => [
            'medio_pago_id' => $medio->id,
        ]);
    }

    /**
     * Estado para multa en efectivo
     */
    public function enEfectivo(): static
    {
        return $this->state(function (array $attributes) {
            $medio = MedioDePago::where('nombre_corto', 'EF')->first() 
                ?? MedioDePago::factory()->efectivo()->create();
            
            return [
                'medio_pago_id' => $medio->id,
                'forma_pago' => 'contado',
            ];
        });
    }

    /**
     * Estado para multa con fecha específica
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => $fecha,
        ]);
    }
}
