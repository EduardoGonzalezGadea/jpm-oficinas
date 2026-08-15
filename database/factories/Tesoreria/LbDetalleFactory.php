<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para LbDetalle
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\LbDetalle>
 */
class LbDetalleFactory extends Factory
{
    protected $model = LbDetalle::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'concepto_id' => LbConcepto::factory(),
            'nombre' => 'Fondo Fijo',
        ];
    }

    /**
     * Estado para detalle Fondo Fijo
     */
    public function fondoFijo(): static
    {
        return $this->state(function (array $attributes) {
            $concepto = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first() 
                ?? LbConcepto::factory()->cajaChica()->create();
            
            return [
                'concepto_id' => $concepto->id,
                'nombre' => LbDetalle::FONDO_FIJO,
            ];
        });
    }

    /**
     * Estado para detalle Pendiente
     */
    public function pendiente(): static
    {
        return $this->state(function (array $attributes) {
            $concepto = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first() 
                ?? LbConcepto::factory()->cajaChica()->create();
            
            return [
                'concepto_id' => $concepto->id,
                'nombre' => LbDetalle::PENDIENTE,
            ];
        });
    }

    /**
     * Estado para detalle Pagos
     */
    public function pagos(): static
    {
        return $this->state(function (array $attributes) {
            $concepto = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first() 
                ?? LbConcepto::factory()->cajaChica()->create();
            
            return [
                'concepto_id' => $concepto->id,
                'nombre' => LbDetalle::PAGOS,
            ];
        });
    }

    /**
     * Estado para detalle con concepto específico
     */
    public function paraConcepto(LbConcepto $concepto): static
    {
        return $this->state(fn (array $attributes) => [
            'concepto_id' => $concepto->id,
        ]);
    }

    /**
     * Estado para detalle con nombre específico
     */
    public function conNombre(string $nombre): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => $nombre,
        ]);
    }
}
