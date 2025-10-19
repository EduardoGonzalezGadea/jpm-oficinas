<?php

namespace App\Models;

use App\Traits\ConvertirMayusculas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use HasFactory, SoftDeletes, ConvertirMayusculas;

    protected $table = 'tes_cd_pagos';

    protected $fillable = [
        'fecha',
        'monto',
        'medio_pago',
        'descripcion',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];

    public function setMedioPagoAttribute($value)
    {
        $this->attributes['medio_pago'] = $this->toUpper($value);
    }

    public function setDescripcionAttribute($value)
    {
        $this->attributes['descripcion'] = $this->toUpper($value);
    }
}
