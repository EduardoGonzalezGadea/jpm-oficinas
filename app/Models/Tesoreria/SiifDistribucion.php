<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;

class SiifDistribucion extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'siif_distribucions';

    protected $fillable = [
        'tipo_id',
        'dependencia_id',
        'rubro',
        'sub_rubro',
        'recurso',
        'concepto',
        'codigo_sir',
        'porcentaje',
        'financiacion',
        'inciso',
        'unidad_ejecutora',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'porcentaje' => 'decimal:3',
        'tipo_id' => 'integer',
        'dependencia_id' => 'integer',
    ];

    public function tipo()
    {
        return $this->belongsTo(SiifDistribucionTipo::class, 'tipo_id');
    }

    public function dependencia()
    {
        return $this->belongsTo(SiifDistribucionDependencia::class, 'dependencia_id');
    }

    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('concepto', 'like', '%' . $term . '%')
              ->orWhere('rubro', 'like', '%' . $term . '%')
              ->orWhere('codigo_sir', 'like', '%' . $term . '%')
              ->orWhere('recurso', 'like', '%' . $term . '%')
              ->orWhereHas('tipo', fn($t) => $t->where('tipo', 'like', '%' . $term . '%'))
              ->orWhereHas('dependencia', fn($d) => $d->where('dependencia', 'like', '%' . $term . '%'));
        });
    }

    public function scopeOrdenado($query)
    {
        return $query->select('siif_distribucions.*')
            ->join('siif_distribucion_tipos', 'siif_distribucions.tipo_id', '=', 'siif_distribucion_tipos.id')
            ->join('siif_distribucion_dependencias', 'siif_distribucions.dependencia_id', '=', 'siif_distribucion_dependencias.id')
            ->orderBy('siif_distribucion_tipos.tipo')
            ->orderBy('siif_distribucion_dependencias.dependencia')
            ->orderBy('siif_distribucions.concepto');
    }
}
