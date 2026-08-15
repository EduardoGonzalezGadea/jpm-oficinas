<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Multa;
use App\Models\Tesoreria\TesMultasCobradas;
use App\Models\Tesoreria\TesMultasItems;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TesMultasItems
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\TesMultasItems>
 */
class TesMultasItemsFactory extends Factory
{
    protected $model = TesMultasItems::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'tes_multas_cobradas_id' => TesMultasCobradas::factory(),
            'tes_multas_id' => Multa::factory(),
            'monto' => $this->faker->randomFloat(2, 100, 5000),
        ];
    }

    /**
     * Estado para item vinculado a multa cobrada específica
     */
    public function paraMultaCobrada(TesMultasCobradas $multaCobrada): static
    {
        return $this->state(fn (array $attributes) => [
            'tes_multas_cobradas_id' => $multaCobrada->id,
        ]);
    }

    /**
     * Estado para item vinculado a multa específica
     */
    public function paraMulta(Multa $multa): static
    {
        return $this->state(fn (array $attributes) => [
            'tes_multas_id' => $multa->id,
        ]);
    }

    /**
     * Estado para item con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'monto' => $monto,
        ]);
    }
}
