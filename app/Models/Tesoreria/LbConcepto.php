<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LbConcepto extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_lb_conceptos';

    protected $fillable = [
        'nombre',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function detalles()
    {
        return $this->hasMany(LbDetalle::class, 'concepto_id');
    }

    public function libroDiario()
    {
        return $this->hasMany(LibroDiario::class, 'concepto_id');
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

    // Constantes para conceptos requeridos del sistema
    public const CAJA_CHICA = 'Caja Chica';
    public const RECAUDACION_222 = 'Recaudación Artículo 222';
    public const RECAUDACION_DIARIA = 'Recaudación Diaria';

    /**
     * Obtiene el concepto Caja Chica o lanza excepción clara.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function cajaChica(): self
    {
        $concepto = static::where('nombre', self::CAJA_CHICA)->first();
        
        if (!$concepto) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el concepto "' . self::CAJA_CHICA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Conceptos. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $concepto;
    }

    /**
     * Obtiene el concepto Recaudación Artículo 222 o lanza excepción clara.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function recaudacion222(): self
    {
        $concepto = static::where('nombre', self::RECAUDACION_222)->first();
        
        if (!$concepto) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el concepto "' . self::RECAUDACION_222 . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Conceptos. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $concepto;
    }

    /**
     * Obtiene el concepto Recaudación Diaria o lanza excepción clara.
     * 
     * @return self
     * @throws \RuntimeException
     */
    public static function recaudacionDiaria(): self
    {
        $concepto = static::where('nombre', self::RECAUDACION_DIARIA)->first();
        
        if (!$concepto) {
            throw new \RuntimeException(
                'ERROR DE CONFIGURACIÓN: No existe el concepto "' . self::RECAUDACION_DIARIA . '" en el Libro Diario. ' .
                'Debe crearlo manualmente en: Tesorería → Libro Diario → Conceptos. ' .
                'Contacte al administrador del sistema si el problema persiste.'
            );
        }
        
        return $concepto;
    }
}
