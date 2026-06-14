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
}
