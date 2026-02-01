<?php
// app/Models/Tesoreria/Cheque.php
namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;

class Cheque extends Model
{
    use SoftDeletes, ConvertirMayusculas, LogsActivityTrait;

    protected $table = 'tes_cheques';
    protected $fillable = [
        'cuenta_bancaria_id',
        'serie',
        'numero_cheque',
        'fecha_emision',
        'beneficiario',
        'monto',
        'concepto',
        'estado',
        'planilla_id',
        'fecha_anulacion',
        'motivo_anulacion',
        'fecha_planilla_anulada',
        'planilla_anulada_por',
        'emitido_por',
        'anulado_por',
        'created_by',
        'updated_by'
    ];

    protected $dates = ['fecha_emision', 'fecha_anulacion', 'fecha_planilla_anulada'];

    /**
     * Campos que deben convertirse a mayúsculas (excluyendo estados)
     */
    protected $camposAMayusculas = [
        'beneficiario',
        'concepto',
        'motivo_anulacion',
        'emitido_por',
        'anulado_por',
        'planilla_anulada_por'
    ];

    /**
     * Campos de estado que NO deben convertirse a mayúsculas
     */
    protected $camposEstado = [
        'estado'
    ];

    public function cuentaBancaria()
    {
        return $this->belongsTo(CuentaBancaria::class, 'cuenta_bancaria_id');
    }

    public function planilla()
    {
        return $this->belongsTo(PlanillaCheque::class, 'planilla_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($m) => $m->created_by = auth()->id());
        static::updating(fn($m) => $m->updated_by = auth()->id());
        static::deleting(fn($m) => $m->deleted_by = auth()->id());
    }

    public function scopeSinAnulacionesPlanilla($query)
    {
        return $query->whereNull('fecha_planilla_anulada');
    }

    /**
     * Convertir campos de texto a mayúsculas antes de guardar
     */
    public function setAttribute($key, $value)
    {
        // Si el campo está en la lista de campos a convertir a mayúsculas
        if (in_array($key, $this->camposAMayusculas)) {
            $value = $this->toUpper($value);
        }

        return parent::setAttribute($key, $value);
    }
}
