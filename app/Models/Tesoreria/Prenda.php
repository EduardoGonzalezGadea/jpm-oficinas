<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Prenda extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas, LogsActivityTrait;

    protected $table = 'tes_prendas';

    protected $fillable = [
        'planilla_id',
        'recibo_serie',
        'recibo_numero',
        'recibo_fecha',
        'orden_cobro',
        'titular_nombre',
        'titular_cedula',
        'titular_telefono',
        'medio_pago_id',
        'monto',
        'concepto',
        'transferencia',
        'transferencia_fecha',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $uppercaseFields = [
        'recibo_serie',
        'recibo_numero',
        'orden_cobro',
        'titular_nombre',
        'titular_cedula',
        'concepto',
        'transferencia',
    ];

    protected $casts = [
        'recibo_fecha' => 'date',
        'transferencia_fecha' => 'date',
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

    public function medioPago()
    {
        return $this->belongsTo(MedioDePago::class, 'medio_pago_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function planilla()
    {
        return $this->belongsTo(PrendaPlanilla::class, 'planilla_id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
    public function getMontoFormateadoAttribute()
    {
        return number_format($this->monto, 2, ',', '.');
    }
}
