<?php

namespace App\Models\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConceptoCobroCampo extends Model
{
    use HasFactory;

    protected $table = 'tes_cd_conceptos_cobro_campos';

    protected $fillable = [
        'concepto_id',
        'nombre',
        'titulo',
        'tipo',
        'requerido',
        'opciones',
        'orden',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'requerido' => 'boolean',
        'opciones' => 'array',
        'orden' => 'integer'
    ];

    // Relación con concepto
    public function concepto()
    {
        return $this->belongsTo(ConceptoCobro::class, 'concepto_id');
    }

    // Relación con valores de cobros
    public function valores()
    {
        return $this->hasMany(CobroCampoValor::class, 'campo_id');
    }

    // Scope para ordenar por orden
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}
