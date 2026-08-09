<?php

namespace App\Models\Tesoreria\Cajas;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CajaConcepto extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_cajas_conceptos';

    protected $fillable = ['nombre', 'tipo', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopeIngresos($query)
    {
        return $query->whereIn('tipo', ['INGRESO', 'AMBOS']);
    }

    public function scopeEgresos($query)
    {
        return $query->whereIn('tipo', ['EGRESO', 'AMBOS']);
    }
}