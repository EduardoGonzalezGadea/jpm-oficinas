<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class MedioDePago extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_medio_de_pagos';

    protected $fillable = [
        'nombre',
        'nombre_corto',
        'descripcion',
        'activo',
        'contado',
        'codigo_soniar',
        'es_libro_diario',
        'es_recaudacion',
        'orden',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'contado' => 'boolean',
        'es_libro_diario' => 'boolean',
        'es_recaudacion' => 'boolean',
        'orden' => 'integer',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden')->orderBy('nombre');
    }

    public function scopeLibroDiario($query)
    {
        return $query->where('es_libro_diario', true);
    }

    public function scopeRecaudacion($query)
    {
        return $query->where('es_recaudacion', true);
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('nombre', 'like', '%' . $term . '%')
                ->orWhere('nombre_corto', 'like', '%' . $term . '%')
                ->orWhere('descripcion', 'like', '%' . $term . '%');
        });
    }

    // Constantes para medios de pago requeridos del sistema
    public const EFECTIVO = 'Efectivo';
    public const TRANSFERENCIA = 'Transferencia';

    /**
     * Obtiene el medio de pago Efectivo o lanza excepción clara.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function efectivo(): self
    {
        $medio = static::where('nombre', self::EFECTIVO)
            ->where('activo', true)
            ->first();
        
        if (!$medio) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el medio de pago "' . self::EFECTIVO . '" activo en el sistema. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Medios de Pago. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $medio;
    }

    /**
     * Obtiene el medio de pago Transferencia o lanza excepción clara.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function transferencia(): self
    {
        $medio = static::where('nombre', 'like', '%' . self::TRANSFERENCIA . '%')
            ->where('activo', true)
            ->first();
        
        if (!$medio) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe un medio de pago que contenga "' . self::TRANSFERENCIA . '" activo en el sistema. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Medios de Pago. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $medio;
    }
}
