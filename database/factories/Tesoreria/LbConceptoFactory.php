<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para LbConcepto
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\LbConcepto>
 */
class LbConceptoFactory extends Factory
{
    protected $model = LbConcepto::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Caja Chica',
        ];
    }

    /**
     * Estado para concepto Caja Chica
     */
    public function cajaChica(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => LbConcepto::CAJA_CHICA,
        ]);
    }

    /**
     * Estado para concepto Recaudación 222
     */
    public function recaudacion222(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => LbConcepto::RECAUDACION_222,
        ]);
    }

    /**
     * Estado para concepto Recaudación Diaria
     */
    public function recaudacionDiaria(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => LbConcepto::RECAUDACION_DIARIA,
        ]);
    }

    /**
     * Estado para concepto con nombre personalizado
     */
    public function conNombre(string $nombre): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => $nombre,
        ]);
    }
}
