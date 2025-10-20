<?php

namespace App\Models\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\ConvertirMayusculas;
use Illuminate\Support\Facades\Auth;

class ConceptoPago extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas;

    protected $table = 'tes_cd_conceptos_pago';

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

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($concepto) {
            if (Auth::check()) {
                $concepto->deleted_by = Auth::id();
                $concepto->save();
            }
        });
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(\App\Models\Pago::class, 'concepto_id');
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

    // Scope para filtrar activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
