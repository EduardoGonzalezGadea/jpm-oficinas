<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\ConvertirMayusculas;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CertificadoResidencia extends Model
{
    use HasFactory, ConvertirMayusculas, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_certificados_residencia';

    protected $fillable = [
        'fecha_recibido',
        'receptor_id',
        'titular_nombre',
        'titular_apellido',
        'titular_tipo_documento',
        'titular_nro_documento',
        'fecha_entregado',
        'entregador_id',
        'retira_nombre',
        'retira_apellido',
        'retira_tipo_documento',
        'retira_nro_documento',
        'retira_telefono',
        'numero_recibo',
        'fecha_devuelto',
        'devolucion_user_id',
        'estado',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Campos que deben ser convertidos a mayúsculas
    protected $uppercaseFields = [
        'titular_nombre',
        'titular_apellido',
        'titular_nro_documento',
        'retira_nombre',
        'retira_apellido',
        'retira_nro_documento',
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
        return $this->belongsTo(User::class, 'devolucion_user_id'); // Corrected foreign key
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
