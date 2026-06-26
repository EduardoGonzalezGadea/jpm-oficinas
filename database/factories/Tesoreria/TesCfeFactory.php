<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use Illuminate\Database\Eloquent\Factories\Factory;

class TesCfeFactory extends Factory
{
    protected $model = TesCfe::class;

    public function definition(): array
    {
        return [
            'documento_tipo' => 'E-Factura Cobranza',
            'documento_serie' => 'A',
            'documento_numero' => $this->faker->unique()->numerify('########'),
            'fecha' => now(),
            'receptor_nombre_denominacion' => $this->faker->company(),
            'receptor_documento_ruc' => $this->faker->numerify('###########'),
            'moneda' => 'UYU',
            'total_a_pagar' => $this->faker->randomFloat(2, 100, 10000),
            'emisor_nombre' => 'Jefatura de Policía de Montevideo',
            'emisor_ruc' => '214988770019',
        ];
    }

    public function conItems(int $cantidad = 2, array $itemOverrides = []): static
    {
        return $this->afterCreating(function (TesCfe $cfe) use ($cantidad, $itemOverrides) {
            TesCfeItemFactory::new()->count($cantidad)->create(
                array_merge(['tes_cfe_id' => $cfe->id], $itemOverrides)
            );
        });
    }

    public function conMediosPago(int $cantidad = 1, array $mpOverrides = []): static
    {
        return $this->afterCreating(function (TesCfe $cfe) use ($cantidad, $mpOverrides) {
            TesCfeMedioPagoFactory::new()->count($cantidad)->create(
                array_merge(['tes_cfe_id' => $cfe->id], $mpOverrides)
            );
        });
    }
}
