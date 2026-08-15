<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\MedioDePago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para MedioDePago
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\MedioDePago>
 */
class MedioDePagoFactory extends Factory
{
    protected $model = MedioDePago::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        return [
            'nombre' => 'Efectivo',
            'nombre_corto' => 'EF',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ];
    }

    /**
     * Estado para medio de pago Efectivo
     */
    public function efectivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Efectivo',
            'nombre_corto' => 'EF',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);
    }

    /**
     * Estado para medio de pago Cheque
     */
    public function cheque(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Cheque',
            'nombre_corto' => 'CH',
            'activo' => true,
            'contado' => false,
            'es_libro_diario' => true,
        ]);
    }

    /**
     * Estado para medio de pago Tarjeta de Débito
     */
    public function tarjetaDebito(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Tarjeta de Débito',
            'nombre_corto' => 'TD',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);
    }

    /**
     * Estado para medio de pago Tarjeta de Crédito
     */
    public function tarjetaCredito(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Tarjeta de Crédito',
            'nombre_corto' => 'TC',
            'activo' => true,
            'contado' => false,
            'es_libro_diario' => true,
        ]);
    }

    /**
     * Estado para medio de pago Transferencia
     */
    public function transferencia(): static
    {
        return $this->state(fn (array $attributes) => [
            'nombre' => 'Transferencia Bancaria',
            'nombre_corto' => 'TB',
            'activo' => true,
            'contado' => true,
            'es_libro_diario' => true,
        ]);
    }

    /**
     * Estado para medio de pago inactivo
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Estado para medio de pago que NO va al libro diario
     */
    public function sinLibroDiario(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_libro_diario' => false,
        ]);
    }
}
