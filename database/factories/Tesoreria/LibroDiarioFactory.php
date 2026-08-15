<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\LibroDiario;
use App\Models\Tesoreria\MedioDePago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para LibroDiario
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\LibroDiario>
 */
class LibroDiarioFactory extends Factory
{
    protected $model = LibroDiario::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        $monto = $this->faker->randomFloat(2, 100, 10000);
        $signo = $this->faker->randomElement([1, -1]);
        
        return [
            'fecha' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'tipo_id' => LbTipo::factory(),
            'numero' => $this->faker->numberBetween(1, 9999),
            'signo_efectivo' => $signo,
            'identidad' => null,
            'denominacion' => null,
            'descripcion' => $this->faker->optional()->sentence(),
            'concepto_id' => LbConcepto::factory(),
            'detalle_id' => LbDetalle::factory(),
            'medio_id' => MedioDePago::factory(),
            'monto' => $monto,
            'saldo' => $monto * $signo,
            'asociar' => null,
            'grupo_redistribucion_id' => null,
            'cfe_id' => null,
            'es_contra_asiento' => false,
            'documento_referencia' => null,
            'confirmado' => false,
            'fecha_confirmacion' => null,
            'cch_origen_type' => null,
            'cch_origen_id' => null,
        ];
    }

    /**
     * Estado para asiento de entrada (positivo)
     */
    public function entrada(): static
    {
        return $this->state(function (array $attributes) {
            $tipo = LbTipo::where('nombre', 'Entrada')->first() ?? LbTipo::factory()->entrada()->create();
            
            return [
                'tipo_id' => $tipo->id,
                'signo_efectivo' => 1,
                'saldo' => abs($attributes['monto']),
            ];
        });
    }

    /**
     * Estado para asiento de salida (negativo)
     */
    public function salida(): static
    {
        return $this->state(function (array $attributes) {
            $tipo = LbTipo::where('nombre', 'Salida')->first() ?? LbTipo::factory()->salida()->create();
            
            return [
                'tipo_id' => $tipo->id,
                'signo_efectivo' => -1,
                'saldo' => -abs($attributes['monto']),
            ];
        });
    }

    /**
     * Estado para asiento de redistribución
     */
    public function redistribucion(int $grupoId = null): static
    {
        return $this->state(function (array $attributes) use ($grupoId) {
            $tipo = LbTipo::where('nombre', 'Redistribución')->first() ?? LbTipo::factory()->redistribucion()->create();
            
            return [
                'tipo_id' => $tipo->id,
                'signo_efectivo' => 0,
                'grupo_redistribucion_id' => $grupoId ?? LibroDiario::generarGrupoRedistribucionId(),
            ];
        });
    }

    /**
     * Estado para asiento confirmado
     */
    public function confirmado(string $fecha = null): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmado' => true,
            'fecha_confirmacion' => $fecha ?? now(),
        ]);
    }

    /**
     * Estado para asiento con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(function (array $attributes) use ($monto) {
            return [
                'monto' => $monto,
                'saldo' => $monto * $attributes['signo_efectivo'],
            ];
        });
    }

    /**
     * Estado para asiento con saldo específico
     */
    public function conSaldo(float $saldo): static
    {
        return $this->state(fn (array $attributes) => [
            'saldo' => $saldo,
        ]);
    }

    /**
     * Estado para asiento de caja chica
     */
    public function cajaChica(): static
    {
        return $this->state(function (array $attributes) {
            $concepto = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first() 
                ?? LbConcepto::factory()->cajaChica()->create();
            
            return [
                'concepto_id' => $concepto->id,
            ];
        });
    }

    /**
     * Estado para asiento vinculado a origen de caja chica
     */
    public function conOrigen(string $type, int $id): static
    {
        return $this->state(fn (array $attributes) => [
            'cch_origen_type' => $type,
            'cch_origen_id' => $id,
        ]);
    }

    /**
     * Estado para asiento con fecha específica
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha' => $fecha,
        ]);
    }

    /**
     * Estado para contra-asiento
     */
    public function contraAsiento(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_contra_asiento' => true,
        ]);
    }
}
