<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\TesCfe;
use App\Models\Tesoreria\CajaConcepto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para TesCfe (Comprobantes Fiscales Electrónicos)
 * 
 * Estados disponibles:
 * - eFactura(): CFE tipo eFactura
 * - eTicket(): CFE tipo eTicket
 * - pendiente(): CFE pendiente de procesamiento
 * - confirmado(): CFE confirmado
 */
class TesCfeFactory extends Factory
{
    protected $model = TesCfe::class;

    public function definition(): array
    {
        return [
            'documento_tipo' => 'eFactura',
            'documento_serie' => 'A',
            'documento_numero' => $this->faker->numerify('######'),
            'fecha' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'vencimiento' => $this->faker->dateTimeBetween('now', '+30 days'),
            'receptor_nombre_denominacion' => $this->faker->company(),
            'receptor_ruc' => $this->faker->numerify('21########0018'),
            'receptor_domicilio' => $this->faker->address(),
            'monto_no_facturable' => 0.00,
            'monto_total' => $montoTotal = $this->faker->randomFloat(2, 1000, 10000),
            'total_a_pagar' => $montoTotal,
            'referencias' => null,
            'adenda' => null,
            'pdf_file_name' => null,
            'pdf_hash' => null,
            'status' => 'pendiente',
            'tes_caja_concepto_id' => null,
            'siif_distribucion_dependencia_id' => null,
            'institucion_id' => null,
            'planilla_comun_id' => null,
        ];
    }

    /**
     * CFE tipo eFactura
     */
    public function eFactura(): static
    {
        return $this->state(fn (array $attributes) => [
            'documento_tipo' => 'eFactura',
            'documento_serie' => $this->faker->randomElement(['A', 'B', 'C']),
        ]);
    }

    /**
     * CFE tipo eTicket
     */
    public function eTicket(): static
    {
        return $this->state(fn (array $attributes) => [
            'documento_tipo' => 'eTicket',
            'documento_serie' => 'T',
        ]);
    }

    /**
     * CFE tipo eRemito
     */
    public function eRemito(): static
    {
        return $this->state(fn (array $attributes) => [
            'documento_tipo' => 'eRemito',
            'documento_serie' => 'R',
        ]);
    }

    /**
     * CFE pendiente de procesamiento
     */
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pendiente',
        ]);
    }

    /**
     * CFE confirmado
     */
    public function confirmado(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmado',
        ]);
    }

    /**
     * CFE con PDF asociado
     */
    public function conPdf(): static
    {
        return $this->state(fn (array $attributes) => [
            'pdf_file_name' => 'cfe_' . $this->faker->uuid() . '.pdf',
            'pdf_hash' => $this->faker->sha256(),
        ]);
    }

    /**
     * CFE con concepto de caja
     */
    public function conConcepto(CajaConcepto $concepto = null): static
    {
        return $this->state(fn (array $attributes) => [
            'tes_caja_concepto_id' => $concepto?->id ?? CajaConcepto::factory(),
        ]);
    }

    /**
     * CFE con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_total' => $monto,
            'total_a_pagar' => $monto,
        ]);
    }
}
