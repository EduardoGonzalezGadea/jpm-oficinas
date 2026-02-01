<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class DepositoVehiculo extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas, LogsActivityTrait;

    protected $table = 'tes_deposito_vehiculos';

    protected $fillable = [
        'titular',
        'cedula',
        'telefono',
        'recibo_serie',
        'recibo_numero',
        'recibo_fecha',
        'orden_cobro',
        'medio_pago_id',
        'monto',
        'concepto',
        'planilla_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'recibo_fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    protected $uppercaseFields = [
        'titular',
        'recibo_serie',
        'recibo_numero',
        'orden_cobro',
        'concepto',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->created_by = Auth::id();
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (Auth::check()) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->uppercaseFields) && is_string($value)) {
            $this->attributes[$key] = $this->toUpper($value);
        } else {
            parent::setAttribute($key, $value);
        }
    }

    /**
     * Relación con MedioDePago
     */
    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    /**
     * Relación con DepositoVehiculoPlanilla
     */
    public function planilla()
    {
        return $this->belongsTo(DepositoVehiculoPlanilla::class, 'planilla_id');
    }

    /**
     * Relación con el usuario creador
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Relación con el usuario que actualizó
     */
    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Relación con el usuario que eliminó
     */
    public function deletedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'deleted_by');
    }
}
