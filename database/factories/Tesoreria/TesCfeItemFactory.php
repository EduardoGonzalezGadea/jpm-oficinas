<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfeItem;
use App\Models\Tesoreria\TesCfe;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TesCfeItem (Items de CFE)
 */
class TesCfeItemFactory extends Factory
{
    protected $model = TesCfeItem::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 10);
        $precioUnitario = $this->faker->randomFloat(2, 100, 5000);
        $subtotal = $cantidad * $precioUnitario;

        return [
            'tes_cfe_id' => TesCfe::factory(),
            'descripcion' => $this->faker->sentence(6),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'detalle' => null,
        ];
    }

    /**
     * Item para un CFE específico
     */
    public function paraCfe(TesCfe $cfe): static
    {
        return $this->state(fn (array $attributes) => [
            'tes_cfe_id' => $cfe->id,
        ]);
    }

    /**
     * Item con cantidad específica
     */
    public function conCantidad(int $cantidad): static
    {
        return $this->state(function (array $attributes) use ($cantidad) {
            $subtotal = $cantidad * $attributes['precio_unitario'];
            return [
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
        });
    }

    /**
     * Item con precio específico
     */
    public function conPrecio(float $precio): static
    {
        return $this->state(function (array $attributes) use ($precio) {
            $subtotal = $attributes['cantidad'] * $precio;
            return [
                'precio_unitario' => $precio,
                'subtotal' => $subtotal,
            ];
        });
    }
}
