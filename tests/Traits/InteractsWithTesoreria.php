<?php

namespace Tests\Traits;

use App\Models\Tesoreria\Acreedor;
use App\Models\Tesoreria\CajaChica;
use App\Models\Tesoreria\LbConcepto;
use App\Models\Tesoreria\LbDetalle;
use App\Models\Tesoreria\LbTipo;
use App\Models\Tesoreria\MedioDePago;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Trait InteractsWithTesoreria
 * 
 * Proporciona helpers comunes para tests de módulos de Tesorería.
 * Incluye creación de datos básicos necesarios para la mayoría de tests.
 */
trait InteractsWithTesoreria
{
    /**
     * Crea los tipos de libro diario básicos (Entrada, Salida, Redistribución)
     */
    protected function crearTiposLibroDiario(): void
    {
        if (LbTipo::count() === 0) {
            LbTipo::create(['nombre' => 'Entrada', 'signo' => 1]);
            LbTipo::create(['nombre' => 'Salida', 'signo' => -1]);
            LbTipo::create(['nombre' => 'Redistribución', 'signo' => 0]);
        }
    }

    /**
     * Crea los conceptos básicos de libro diario
     */
    protected function crearConceptosLibroDiario(): array
    {
        $conceptos = [];
        
        if (!LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->exists()) {
            $conceptos['caja_chica'] = LbConcepto::create(['nombre' => LbConcepto::CAJA_CHICA]);
        } else {
            $conceptos['caja_chica'] = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->first();
        }

        if (!LbConcepto::where('nombre', LbConcepto::RECAUDACION_222)->exists()) {
            $conceptos['recaudacion_222'] = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_222]);
        } else {
            $conceptos['recaudacion_222'] = LbConcepto::where('nombre', LbConcepto::RECAUDACION_222)->first();
        }

        if (!LbConcepto::where('nombre', LbConcepto::RECAUDACION_DIARIA)->exists()) {
            $conceptos['recaudacion_diaria'] = LbConcepto::create(['nombre' => LbConcepto::RECAUDACION_DIARIA]);
        } else {
            $conceptos['recaudacion_diaria'] = LbConcepto::where('nombre', LbConcepto::RECAUDACION_DIARIA)->first();
        }

        return $conceptos;
    }

    /**
     * Crea los detalles básicos para caja chica
     */
    protected function crearDetallesCajaChica(): array
    {
        $conceptoCajaChica = LbConcepto::where('nombre', LbConcepto::CAJA_CHICA)->firstOrFail();
        
        $detalles = [];
        
        if (!LbDetalle::where('nombre', LbDetalle::FONDO_FIJO)->exists()) {
            $detalles['fondo_fijo'] = LbDetalle::create([
                'concepto_id' => $conceptoCajaChica->id,
                'nombre' => LbDetalle::FONDO_FIJO
            ]);
        } else {
            $detalles['fondo_fijo'] = LbDetalle::where('nombre', LbDetalle::FONDO_FIJO)->first();
        }

        if (!LbDetalle::where('nombre', LbDetalle::PENDIENTE)->exists()) {
            $detalles['pendiente'] = LbDetalle::create([
                'concepto_id' => $conceptoCajaChica->id,
                'nombre' => LbDetalle::PENDIENTE
            ]);
        } else {
            $detalles['pendiente'] = LbDetalle::where('nombre', LbDetalle::PENDIENTE)->first();
        }

        if (!LbDetalle::where('nombre', LbDetalle::PAGOS)->exists()) {
            $detalles['pagos'] = LbDetalle::create([
                'concepto_id' => $conceptoCajaChica->id,
                'nombre' => LbDetalle::PAGOS
            ]);
        } else {
            $detalles['pagos'] = LbDetalle::where('nombre', LbDetalle::PAGOS)->first();
        }

        return $detalles;
    }

    /**
     * Crea los medios de pago básicos
     */
    protected function crearMediosDePago(): array
    {
        $medios = [];

        if (!MedioDePago::where('nombre_corto', 'EF')->exists()) {
            $medios['efectivo'] = MedioDePago::create([
                'nombre' => 'Efectivo',
                'nombre_corto' => 'EF',
                'activo' => true,
                'contado' => true,
                'es_libro_diario' => true,
            ]);
        } else {
            $medios['efectivo'] = MedioDePago::where('nombre_corto', 'EF')->first();
        }

        if (!MedioDePago::where('nombre_corto', 'CH')->exists()) {
            $medios['cheque'] = MedioDePago::create([
                'nombre' => 'Cheque',
                'nombre_corto' => 'CH',
                'activo' => true,
                'contado' => false,
                'es_libro_diario' => true,
            ]);
        } else {
            $medios['cheque'] = MedioDePago::where('nombre_corto', 'CH')->first();
        }

        if (!MedioDePago::where('nombre_corto', 'TD')->exists()) {
            $medios['tarjeta_debito'] = MedioDePago::create([
                'nombre' => 'Tarjeta de Débito',
                'nombre_corto' => 'TD',
                'activo' => true,
                'contado' => true,
                'es_libro_diario' => true,
            ]);
        } else {
            $medios['tarjeta_debito'] = MedioDePago::where('nombre_corto', 'TD')->first();
        }

        return $medios;
    }

    /**
     * Crea un acreedor de prueba
     */
    protected function crearAcreedor(array $attributes = []): Acreedor
    {
        return Acreedor::create(array_merge([
            'acreedor' => 'Proveedor de Prueba ' . uniqid(),
        ], $attributes));
    }

    /**
     * Crea el acreedor BSE (Banco de Seguros del Estado)
     */
    protected function crearAcreedorBSE(): Acreedor
    {
        $bse = Acreedor::where('acreedor', 'like', '%Banco de Seguros del Estado%')->first();
        
        if (!$bse) {
            $bse = Acreedor::create(['acreedor' => 'Banco de Seguros del Estado']);
        }

        return $bse;
    }

    /**
     * Crea una caja chica de prueba
     */
    protected function crearCajaChica(array $attributes = []): CajaChica
    {
        return CajaChica::create(array_merge([
            'mes' => 'agosto',
            'anio' => 2026,
            'montoCajaChica' => 5000,
        ], $attributes));
    }

    /**
     * Crea un usuario de prueba con permisos básicos
     */
    protected function crearUsuarioTesoreria(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'Usuario Test ' . uniqid(),
            'email' => 'test_' . uniqid() . '@tesoreria.test',
            'password' => Hash::make('password'),
            'activo' => true,
        ], $attributes));
    }

    /**
     * Configura los datos básicos de Tesorería necesarios para la mayoría de tests
     * 
     * Incluye:
     * - Tipos de libro diario
     * - Conceptos básicos
     * - Detalles de caja chica
     * - Medios de pago
     */
    protected function setupDatosBasicosTesoreria(): array
    {
        $this->crearTiposLibroDiario();
        $conceptos = $this->crearConceptosLibroDiario();
        $detalles = $this->crearDetallesCajaChica();
        $medios = $this->crearMediosDePago();

        return [
            'tipos' => LbTipo::all(),
            'conceptos' => $conceptos,
            'detalles' => $detalles,
            'medios' => $medios,
        ];
    }

    /**
     * Obtiene un tipo de libro diario por nombre
     */
    protected function getTipo(string $nombre): ?LbTipo
    {
        return LbTipo::where('nombre', $nombre)->first();
    }

    /**
     * Obtiene un concepto por nombre
     */
    protected function getConcepto(string $nombre): ?LbConcepto
    {
        return LbConcepto::where('nombre', $nombre)->first();
    }

    /**
     * Obtiene un detalle por nombre
     */
    protected function getDetalle(string $nombre): ?LbDetalle
    {
        return LbDetalle::where('nombre', $nombre)->first();
    }

    /**
     * Obtiene un medio de pago por nombre corto
     */
    protected function getMedioDePago(string $nombreCorto): ?MedioDePago
    {
        return MedioDePago::where('nombre_corto', $nombreCorto)->first();
    }
}
