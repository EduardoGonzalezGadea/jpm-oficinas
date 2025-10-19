<?php

namespace App\Models\Tesoreria\CajaDiaria;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TesCdCierreDenominacion extends Model
{
    use HasFactory;

    protected $table = 'tes_cd_cierre_denominaciones';

    protected $fillable = [
        'tes_cd_cierres_id',
        'tes_denominaciones_monedas_id',
        'monto',
    ];
}
