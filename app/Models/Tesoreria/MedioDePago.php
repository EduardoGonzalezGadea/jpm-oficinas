<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class MedioDePago extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_medio_de_pagos';

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
