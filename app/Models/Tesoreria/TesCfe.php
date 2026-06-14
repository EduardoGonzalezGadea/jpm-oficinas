<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Models\Tesoreria\CajaConcepto;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class TesCfe extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_cfes';

    protected $guarded = ['id'];

    protected $casts = [
        'vencimiento' => 'date',
        'fecha' => 'date',
        'monto_no_facturable' => 'decimal:2',
        'monto_total' => 'decimal:2',
        'total_a_pagar' => 'decimal:2',
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

    public function items()
    {
        return $this->hasMany(TesCfeItem::class, 'tes_cfe_id');
    }

    public function mediosPago()
    {
        return $this->hasMany(TesCfeMedioPago::class, 'tes_cfe_id');
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

    public function cajaConcepto()
    {
        return $this->belongsTo(CajaConcepto::class, 'tes_caja_concepto_id');
    }

    public function siifDistribucionTipo()
    {
        return $this->belongsTo(SiifDistribucionTipo::class, 'siif_distribucion_tipo_id');
    }

    public function siifDistribucionDependencia()
    {
        return $this->belongsTo(SiifDistribucionDependencia::class, 'siif_distribucion_dependencia_id');
    }
}

