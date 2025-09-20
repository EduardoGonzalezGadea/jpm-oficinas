<?php

namespace App\Models\Tesoreria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TesPorteArmas extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tes_porte_armas';

    protected $fillable = [
        'fecha',
        'orden_cobro',
        'numero_tramite',
        'ingreso_contabilidad',
        'recibo',
        'monto',
        'titular',
        'cedula',
        'telefono',
    ];

    protected $dates = ['fecha'];

    public function getMontoFormateadoAttribute()
    {
        return '$ ' . number_format($this->monto, 2, ',', '.');
    }
}
