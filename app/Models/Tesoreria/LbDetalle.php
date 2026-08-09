<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LbDetalle extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_lb_detalle';

    protected $fillable = [
        'concepto_id',
        'nombre',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function concepto()
    {
        return $this->belongsTo(LbConcepto::class, 'concepto_id');
    }

    public function libroDiario()
    {
        return $this->hasMany(LibroDiario::class, 'detalle_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeActivos($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('nombre');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('nombre', 'like', "%{$term}%");
        });
    }

    // Constantes para detalles requeridos del sistema
    public const FONDO_FIJO = 'Fondo Fijo';
    public const PENDIENTE = 'Pendiente';
    public const PAGOS = 'Pagos';
    public const RECAUDACIONES_VARIAS_222 = 'Recaudaciones varias de Artículo 222';
    public const OTRAS_RECAUDACIONES_VARIAS = 'Otras recaudaciones varias';

    /**
     * Obtiene el detalle Fondo Fijo del concepto Caja Chica.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function fondoFijo(): self
    {
        $conceptoCajaChica = LbConcepto::cajaChica();
        
        $detalle = static::where('concepto_id', $conceptoCajaChica->id)
            ->where('nombre', self::FONDO_FIJO)
            ->first();
        
        if (!$detalle) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el detalle "' . self::FONDO_FIJO . '" ' .
                'bajo el concepto "' . LbConcepto::CAJA_CHICA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Detalles. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $detalle;
    }

    /**
     * Obtiene el detalle Pendiente del concepto Caja Chica.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function pendiente(): self
    {
        $conceptoCajaChica = LbConcepto::cajaChica();
        
        $detalle = static::where('concepto_id', $conceptoCajaChica->id)
            ->where('nombre', self::PENDIENTE)
            ->first();
        
        if (!$detalle) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el detalle "' . self::PENDIENTE . '" ' .
                'bajo el concepto "' . LbConcepto::CAJA_CHICA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Detalles. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $detalle;
    }

    /**
     * Obtiene el detalle Pagos del concepto Caja Chica.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function pagos(): self
    {
        $conceptoCajaChica = LbConcepto::cajaChica();
        
        $detalle = static::where('concepto_id', $conceptoCajaChica->id)
            ->where('nombre', self::PAGOS)
            ->first();
        
        if (!$detalle) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el detalle "' . self::PAGOS . '" ' .
                'bajo el concepto "' . LbConcepto::CAJA_CHICA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Detalles. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $detalle;
    }

    /**
     * Obtiene el detalle "Recaudaciones varias de Artículo 222" del concepto Recaudación Artículo 222.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function recaudacionesVarias222(): self
    {
        $concepto = LbConcepto::recaudacion222();
        
        $detalle = static::where('concepto_id', $concepto->id)
            ->where('nombre', self::RECAUDACIONES_VARIAS_222)
            ->first();
        
        if (!$detalle) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el detalle "' . self::RECAUDACIONES_VARIAS_222 . '" ' .
                'bajo el concepto "' . LbConcepto::RECAUDACION_222 . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Detalles. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $detalle;
    }

    /**
     * Obtiene el detalle "Otras recaudaciones varias" del concepto Recaudación Diaria.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function otrasRecaudacionesVarias(): self
    {
        $concepto = LbConcepto::recaudacionDiaria();
        
        $detalle = static::where('concepto_id', $concepto->id)
            ->where('nombre', self::OTRAS_RECAUDACIONES_VARIAS)
            ->first();
        
        if (!$detalle) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el detalle "' . self::OTRAS_RECAUDACIONES_VARIAS . '" ' .
                'bajo el concepto "' . LbConcepto::RECAUDACION_DIARIA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Detalles. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $detalle;
    }
}
