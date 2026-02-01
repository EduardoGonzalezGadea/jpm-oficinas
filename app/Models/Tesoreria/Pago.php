<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class Pago extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $table = 'tes_cch_pagos';
    protected $primaryKey = 'idPagos';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'relCajaChica_Pagos',
        'fechaEgresoPagos',
        'egresoPagos',
        'relAcreedores',
        'conceptoPagos',
        'montoPagos',
        'fechaIngresoPagos',
        'ingresoPagos',
        'ingresoPagosBSE',
        'recuperadoPagos',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fechaEgresoPagos' => 'date',
        'fechaIngresoPagos' => 'date',
    ];

    protected $dates = ['deleted_at'];

    // Relaciones
    public function cajaChica()
    {
        return $this->belongsTo(CajaChica::class, 'relCajaChica_Pagos', 'idCajaChica');
    }

    public function acreedor()
    {
        return $this->belongsTo(Acreedor::class, 'relAcreedores', 'idAcreedores');
    }

    // Accesor para saldo
    public function getSaldoPagosAttribute()
    {
        return $this->montoPagos - $this->recuperadoPagos;
    }
}
