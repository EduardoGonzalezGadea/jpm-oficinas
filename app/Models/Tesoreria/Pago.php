<?php

namespace App\Models\Tesoreria;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\LogsActivityTrait;

class Pago extends Model
{
    use HasFactory, SoftDeletes, Auditable, LogsActivityTrait;

    protected $table = 'tes_cch_pagos';
    protected $primaryKey = 'idPagos';
    public $timestamps = true; // <-- Cambiado a true

    protected $fillable = [
        'relCajaChica_Pagos',
        'fechaEgresoPagos',
        'fechaEgresoEfectivoPagos',
        'egresoPagos',
        'relAcreedores',
        'conceptoPagos',
        'montoPagos',
        'rendidoPagos',
        'reintegradoPagos',
        'ingresoReintegroPagos',
        'fechaRendicionPagos',
        'recuperadoPagos',
        'fechaIngresoPagos',
        'ingresoPagos',
        'ingresoPagosBSE',
        'fechaIngresoBSEPagos',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'fechaEgresoPagos' => 'date',
        'fechaEgresoEfectivoPagos' => 'date',
        'fechaRendicionPagos' => 'date',
        'fechaIngresoPagos' => 'date',
        'fechaIngresoBSEPagos' => 'date',
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
        $monto = $this->montoPagos;
        $rendido = $this->rendidoPagos;
        $reintegrado = $this->reintegradoPagos;
        $recuperado = $this->recuperadoPagos ?: 0;

        // Si ya se recuperó todo el monto otorgado, el saldo es 0
        if ($recuperado >= $monto && $monto > 0) {
            return 0;
        }

        // Si no se ha rendido ni reintegrado, el saldo es el monto otorgado menos lo recuperado
        if (is_null($rendido) && is_null($reintegrado)) {
            return max(0, round($monto - $recuperado, 2));
        }

        // Si ya se rindió, el saldo es el monto rendido menos lo recuperado
        return max(0, round(($rendido ?: 0) - $recuperado, 2));
    }

    public function tieneDatosRendicion(): bool
    {
        return !is_null($this->rendidoPagos)
            || !is_null($this->reintegradoPagos)
            || !empty($this->fechaRendicionPagos)
            || !empty($this->ingresoReintegroPagos);
    }

    public function tieneDatosRecuperacion(): bool
    {
        return ($this->recuperadoPagos ?? 0) > 0
            || !empty($this->ingresoPagos)
            || !empty($this->fechaIngresoPagos)
            || !empty($this->ingresoPagosBSE)
            || !empty($this->fechaIngresoBSEPagos);
    }

    public function puedeRecuperar(): bool
    {
        return $this->tieneDatosRendicion() && !$this->tieneDatosRecuperacion();
    }
}
