<?php

namespace App\Models\Tesoreria\CajaDiaria;

use App\Traits\ConvertirMayusculas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConceptoCobro extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas;

    protected $table = 'tes_cd_conceptos_cobro';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = $this->toUpper($value);
    }

    public function setDescripcionAttribute($value)
    {
        $this->attributes['descripcion'] = $this->toUpper($value);
    }

    // Relación con campos
    public function campos()
    {
        return $this->hasMany(ConceptoCobroCampo::class, 'concepto_id')->orderBy('orden');
    }

    // Relación con cobros
    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'concepto_id');
    }

    // Scope para conceptos activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar por nombre
    public function scopeOrdenado($query)
    {
        return $query->orderBy('nombre');
    }

    // Scope para búsqueda
    public function scopeSearch($query, $term)
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($query) use ($term) {
            $query->where('nombre', 'like', '%' . $term . '%')
                ->orWhere('descripcion', 'like', '%' . $term . '%');
        });
    }
}
