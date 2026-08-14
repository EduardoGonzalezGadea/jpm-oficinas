<?php

namespace App\Models\Tesoreria;

use App\Models\User;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesPlanillaComun extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_planilla_comunes';

    protected $fillable = [
        'fecha',
        'numero',
        'tes_caja_concepto_id',
        'confirmada',
        'motivo_anulacion',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fecha' => 'date',
        'confirmada' => 'boolean',
    ];

    public function cajaConcepto()
    {
        return $this->belongsTo(CajaConcepto::class, 'tes_caja_concepto_id');
    }

    public function cfes()
    {
        return $this->hasMany(TesCfe::class, 'planilla_comun_id');
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
                ->orWhereHas('cajaConcepto', fn($sq) => $sq->where('caja_concepto', 'like', '%' . $term . '%'));
        });
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('fecha', 'desc')->orderBy('numero', 'desc');
    }
}
