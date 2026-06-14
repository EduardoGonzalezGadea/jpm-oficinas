<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TarjetaCobroBrou extends Model
{
    use HasFactory, ConvertirMayusculas, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_tarjetas_cobro_brou';

    protected $fillable = [
        'fecha_recibido',
        'receptor_id',
        'titular_cedula',
        'titular_nombre',
        'titular_apellido',
        'numero_tarjeta',
        'fecha_entregado',
        'entregador_id',
        'fecha_devuelto',
        'devolucion_user_id',
        'observaciones',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Campos que deben ser convertidos a mayúsculas
    protected $uppercaseFields = [
        'titular_cedula',
        'titular_nombre',
        'titular_apellido',
        'numero_tarjeta',
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
                $model->save(); // Save to persist the deleted_by field
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

    public function receptor()
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function entregador()
    {
        return $this->belongsTo(User::class, 'entregador_id');
    }

    public function devolucionUser()
    {
        return $this->belongsTo(User::class, 'devolucion_user_id');
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
}