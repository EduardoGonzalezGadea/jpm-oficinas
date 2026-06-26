<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesPlanillaEr extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_planilla_ers';

    protected $fillable = [
        'fecha',
        'numero',
        'tipo_id',
        'dependencia_id',
        'turno',
        'er_numero',
        'egresos_numero',
        'ingresos_numero',
        'transferencia_fecha',
        'transferencia_confirmacion',
        'confirmada',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'transferencia_fecha' => 'date',
        'confirmada' => 'boolean',
    ];

    public function tipo()
    {
        return $this->belongsTo(SiifDistribucionTipo::class, 'tipo_id');
    }

    public function dependencia()
    {
        return $this->belongsTo(SiifDistribucionDependencia::class, 'dependencia_id');
    }

    public function items()
    {
        return $this->hasMany(TesCfeItem::class, 'planilla_er_id');
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

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('numero', 'like', '%' . $term . '%')
                ->orWhere('turno', 'like', '%' . $term . '%')
                ->orWhere('er_numero', 'like', '%' . $term . '%')
                ->orWhere('egresos_numero', 'like', '%' . $term . '%')
                ->orWhere('ingresos_numero', 'like', '%' . $term . '%')
                ->orWhere('transferencia_confirmacion', 'like', '%' . $term . '%');
        });
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('fecha', 'desc')->orderBy('numero', 'desc');
    }
}
