<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Traits\LogsActivityTrait;

class CajaConcepto extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tes_caja_conceptos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'caja_concepto',
        'requiere_confirmacion',
        'requiere_distribucion',
        'permite_planilla',
        'requiere_organismo',
        'siif_distribucion_tipo_id',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requiere_confirmacion'      => 'boolean',
        'requiere_distribucion'      => 'boolean',
        'permite_planilla'           => 'boolean',
        'requiere_organismo'         => 'boolean',
        'siif_distribucion_tipo_id'  => 'integer',
    ];

    /**
     * Scope for searching.
     */
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where('caja_concepto', 'like', '%' . $term . '%');
    }

    /**
     * Scope for sorting.
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('caja_concepto');
    }

    /**
     * Tipo de distribución SIIF asociado al concepto.
     */
    public function siifDistribucionTipo(): BelongsTo
    {
        return $this->belongsTo(SiifDistribucionTipo::class, 'siif_distribucion_tipo_id');
    }
}
