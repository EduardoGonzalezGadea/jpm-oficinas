<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedioDePago extends Model
{
    use HasFactory;

    protected $table = 'tes_medio_de_pagos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    // Scope para obtener solo medios de pago activos
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    // Scope para ordenar por nombre
    public function scopeOrdenado($query)
    {
        return $query->orderBy('nombre');
    }
}
