<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'tes_cd_pagos';

    protected $fillable = [
        'fecha',
        'monto',
        'medio_pago',
        'descripcion',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2'
    ];
}
