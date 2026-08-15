<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Multa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Multa
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Multa>
 */
class MultaFactory extends Factory
{
    protected $model = Multa::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        $articulo = $this->faker->numberBetween(100, 999);
        $apartado = $this->faker->optional(0.3)->bothify('?#');
        
        return [
            'codigo' => $this->faker->unique()->numerify('M-####'),
            'articulo' => $articulo,
            'literal' => $this->faker->optional()->randomElement(['A', 'B', 'C', 'D']),
            'apartado' => $apartado,
            'articulo_completo' => $articulo . ($apartado ? '.' . $apartado : ''),
            'descripcion' => $this->faker->sentence(10),
            'moneda' => $this->faker->randomElement(['UYU', 'UR', 'UI']),
            'importe_original' => $this->faker->randomFloat(2, 500, 10000),
            'importe_unificado' => null,
            'decreto' => $this->faker->optional()->numerify('Decreto ###/####'),
            'monto_ur' => null,
            'monto_ui' => null,
            'monto_pesos' => null,
            'inciso_legal' => $this->faker->optional()->sentence(),
            'visible' => true,
        ];
    }

    /**
     * Estado para multa en pesos uruguayos
     */
    public function enPesos(): static
    {
        return $this->state(function (array $attributes) {
            $monto = $this->faker->randomFloat(2, 500, 10000);
            
            return [
                'moneda' => 'UYU',
                'importe_original' => $monto,
                'monto_pesos' => $monto,
            ];
        });
    }

    /**
     * Estado para multa en UR
     */
    public function enUR(): static
    {
        return $this->state(function (array $attributes) {
            $monto = $this->faker->randomFloat(4, 1, 100);
            
            return [
                'moneda' => 'UR',
                'importe_original' => $monto,
                'monto_ur' => $monto,
            ];
        });
    }

    /**
     * Estado para multa en UI
     */
    public function enUI(): static
    {
        return $this->state(function (array $attributes) {
            $monto = $this->faker->randomFloat(4, 1, 100);
            
            return [
                'moneda' => 'UI',
                'importe_original' => $monto,
                'monto_ui' => $monto,
            ];
        });
    }

    /**
     * Estado para multa con artículo específico
     */
    public function articulo(int $articulo, string $apartado = null): static
    {
        return $this->state(fn (array $attributes) => [
            'articulo' => $articulo,
            'apartado' => $apartado,
            'articulo_completo' => $articulo . ($apartado ? '.' . $apartado : ''),
        ]);
    }

    /**
     * Estado para multa Art. 184 (SOA)
     */
    public function articulo184(): static
    {
        return $this->articulo(184)->state(fn (array $attributes) => [
            'descripcion' => 'Multa por incumplimiento Art. 184',
            'moneda' => 'UYU',
        ]);
    }

    /**
     * Estado para multa visible
     */
    public function visible(): static
    {
        return $this->state(fn (array $attributes) => [
            'visible' => true,
        ]);
    }

    /**
     * Estado para multa oculta
     */
    public function oculta(): static
    {
        return $this->state(fn (array $attributes) => [
            'visible' => false,
        ]);
    }

    /**
     * Estado para multa con monto específico
     */
    public function conMonto(float $monto, string $moneda = 'UYU'): static
    {
        return $this->state(function (array $attributes) use ($monto, $moneda) {
            $data = [
                'moneda' => $moneda,
                'importe_original' => $monto,
            ];

            switch ($moneda) {
                case 'UYU':
                    $data['monto_pesos'] = $monto;
                    break;
                case 'UR':
                    $data['monto_ur'] = $monto;
                    break;
                case 'UI':
                    $data['monto_ui'] = $monto;
                    break;
            }

            return $data;
        });
    }
}
