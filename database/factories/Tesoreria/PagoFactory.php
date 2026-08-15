<?php

namespace Database\Factories\Tesoreria;

use App\Models\Tesoreria\Acreedor;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory para Pago
 * 
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tesoreria\Pago>
 */
class PagoFactory extends Factory
{
    protected $model = Pago::class;

    /**
     * Define el estado por defecto del modelo
     */
    public function definition(): array
    {
        $monto = $this->faker->randomFloat(2, 100, 5000);
        
        return [
            'relCajaChica_Pagos' => CajaChica::factory(),
            'fechaEgresoPagos' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'fechaEgresoEfectivoPagos' => null,
            'egresoPagos' => 'EGR-' . strtoupper($this->faker->unique()->bothify('####')),
            'relAcreedores' => Acreedor::factory(),
            'conceptoPagos' => $this->faker->randomElement([
                'Compra de insumos',
                'Servicio de limpieza',
                'Reparaciones',
                'Material de oficina',
                'Gastos varios',
                'Servicios profesionales'
            ]),
            'montoPagos' => $monto,
            'rendidoPagos' => null,
            'reintegradoPagos' => null,
            'ingresoReintegroPagos' => null,
            'fechaRendicionPagos' => null,
            'recuperadoPagos' => null,
            'fechaIngresoPagos' => null,
            'ingresoPagos' => null,
            'ingresoPagosBSE' => null,
            'fechaIngresoBSEPagos' => null,
        ];
    }

    /**
     * Estado para pago vinculado a una caja chica específica
     */
    public function paraCajaChica(CajaChica $cajaChica): static
    {
        return $this->state(fn (array $attributes) => [
            'relCajaChica_Pagos' => $cajaChica->idCajaChica,
        ]);
    }

    /**
     * Estado para pago con acreedor específico
     */
    public function paraAcreedor(Acreedor $acreedor): static
    {
        return $this->state(fn (array $attributes) => [
            'relAcreedores' => $acreedor->idAcreedores,
        ]);
    }

    /**
     * Estado para pago con monto específico
     */
    public function conMonto(float $monto): static
    {
        return $this->state(fn (array $attributes) => [
            'montoPagos' => $monto,
        ]);
    }

    /**
     * Estado para pago rendido
     */
    public function rendido(float $montoRendido = null, string $fecha = null): static
    {
        return $this->state(function (array $attributes) use ($montoRendido, $fecha) {
            $monto = $montoRendido ?? $attributes['montoPagos'];
            $reintegro = $attributes['montoPagos'] - $monto;
            
            return [
                'rendidoPagos' => $monto,
                'reintegradoPagos' => $reintegro > 0 ? $reintegro : null,
                'fechaRendicionPagos' => $fecha ?? now()->addDays(rand(5, 15)),
                'ingresoReintegroPagos' => $reintegro > 0 ? 'REINT-' . strtoupper($this->faker->bothify('####')) : null,
            ];
        });
    }

    /**
     * Estado para pago recuperado (acreedor BSE)
     */
    public function recuperado(float $montoRecuperado = null, bool $conDatosBSE = true): static
    {
        return $this->state(function (array $attributes) use ($montoRecuperado, $conDatosBSE) {
            $recuperado = $montoRecuperado ?? ($attributes['rendidoPagos'] ?? $attributes['montoPagos']);
            
            $data = [
                'recuperadoPagos' => $recuperado,
                'fechaIngresoPagos' => now()->addDays(rand(20, 30)),
                'ingresoPagos' => 'ING-' . strtoupper($this->faker->bothify('####')),
            ];

            if ($conDatosBSE) {
                $data['ingresoPagosBSE'] = 'BSE-' . now()->year . '-' . strtoupper($this->faker->bothify('###'));
                $data['fechaIngresoBSEPagos'] = $data['fechaIngresoPagos'];
            }

            return $data;
        });
    }

    /**
     * Estado para pago completo (rendido y recuperado)
     */
    public function completo(bool $conDatosBSE = false): static
    {
        return $this->rendido()->recuperado(null, $conDatosBSE);
    }

    /**
     * Estado para pago con fecha específica
     */
    public function enFecha(string $fecha): static
    {
        return $this->state(fn (array $attributes) => [
            'fechaEgresoPagos' => $fecha,
        ]);
    }
}
