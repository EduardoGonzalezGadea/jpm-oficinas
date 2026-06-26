<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfeItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class TesCfeItemFactory extends Factory
{
    protected $model = TesCfeItem::class;

    public function definition(): array
    {
        $importe = $this->faker->randomFloat(2, 100, 5000);
        return [
            'detalle' => $this->faker->sentence(3),
            'descripcion' => $this->faker->optional()->sentence(),
            'cantidad' => 1,
            'precio' => $importe,
            'descuento' => 0,
            'recargo' => 0,
            'importe' => $importe,
        ];
    }

    public function conDistribucion(int $siifDistribucionId): static
    {
        return $this->state(fn() => [
            'siif_distribucion_id' => $siifDistribucionId,
        ]);
    }
}
